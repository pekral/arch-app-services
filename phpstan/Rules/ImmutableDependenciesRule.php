<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Illuminate\Database\Eloquent\Model;
use Pekral\Arch\PHPStan\Support\ArchitectureLayerMatcher;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Spatie\LaravelData\Data;

/**
 * Enforces that an architecture-layer class (Action, Service, Repository, Model Manager) receives its
 * dependencies through the constructor and can never replace them afterwards:
 *
 * - a dependency property must be readonly (a readonly class satisfies this for every property);
 * - a dependency property may be assigned only inside the constructor, so setter injection and later
 *   reassignment are reported.
 *
 * A dependency is an instance property whose type is an object. Properties typed as a DTO
 * (Spatie\LaravelData\Data) or an Eloquent model hold data, not collaborators, and are not dependencies.
 * Static properties are left to NoMutableStaticStateRule.
 *
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\ClassPropertiesNode>
 */
final readonly class ImmutableDependenciesRule implements Rule
{

    public const string MUTABLE_DEPENDENCY_IDENTIFIER = 'arch.mutableDependency';

    public const string DEPENDENCY_ASSIGNED_OUTSIDE_CONSTRUCTOR_IDENTIFIER = 'arch.dependencyAssignedOutsideConstructor';

    private const string CONSTRUCTOR = '__construct';

    private const array DATA_TYPES = [
        Data::class,
        Model::class,
    ];

    public function getNodeType(): string
    {
        return ClassPropertiesNode::class;
    }

    /**
     * @param \PHPStan\Node\ClassPropertiesNode $node
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if (!ArchitectureLayerMatcher::isArchitectureClass($classReflection)) {
            return [];
        }

        $dependencyProperties = $this->findDependencyProperties($node);

        if ($dependencyProperties === []) {
            return [];
        }

        $className = $classReflection->getName();

        return [
            ...$this->mutableDependencyErrors($dependencyProperties, $className),
            ...$this->assignedOutsideConstructorErrors($node, $dependencyProperties, $className),
        ];
    }

    /**
     * @return array<string, \PHPStan\Node\ClassPropertyNode>
     */
    private function findDependencyProperties(ClassPropertiesNode $node): array
    {
        $dependencyProperties = [];

        foreach ($node->getProperties() as $property) {
            if ($property->isStatic() || !$this->isDependencyType($property)) {
                continue;
            }

            $dependencyProperties[$property->getName()] = $property;
        }

        return $dependencyProperties;
    }

    private function isDependencyType(ClassPropertyNode $property): bool
    {
        $type = $property->getNativeType() ?? $property->getPhpDocType();

        if ($type === null) {
            return false;
        }

        $objectType = TypeCombinator::removeNull($type);

        if (!$objectType->isObject()->yes()) {
            return false;
        }

        return !$this->isDataType($objectType);
    }

    private function isDataType(Type $type): bool
    {
        foreach (self::DATA_TYPES as $dataType) {
            if (new ObjectType($dataType)->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, \PHPStan\Node\ClassPropertyNode> $dependencyProperties
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function mutableDependencyErrors(array $dependencyProperties, string $className): array
    {
        $errors = [];

        foreach ($dependencyProperties as $property) {
            if ($property->isReadOnly()) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Dependency property %s::$%s must be readonly. Inject it through the constructor and declare it readonly'
                    . ' (or make the class readonly) so it cannot be replaced after construction.',
                    $className,
                    $property->getName(),
                ),
            )
                ->identifier(self::MUTABLE_DEPENDENCY_IDENTIFIER)
                ->line($property->getStartLine())
                ->build();
        }

        return $errors;
    }

    /**
     * @param array<string, \PHPStan\Node\ClassPropertyNode> $dependencyProperties
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function assignedOutsideConstructorErrors(ClassPropertiesNode $node, array $dependencyProperties, string $className): array
    {
        $errors = [];

        foreach ($node->getClass()->getMethods() as $method) {
            $methodName = $method->name->toString();

            if ($methodName === self::CONSTRUCTOR) {
                continue;
            }

            foreach ($this->findOwnPropertyAssignments($method) as [$propertyName, $line]) {
                if (!isset($dependencyProperties[$propertyName])) {
                    continue;
                }

                $errors[] = $this->assignedOutsideConstructorError($className, $propertyName, $methodName, $line);
            }
        }

        return $errors;
    }

    /**
     * Every `$this->property` written inside the method, as [property name, line] pairs.
     *
     * @return list<array{string, int}>
     */
    private function findOwnPropertyAssignments(ClassMethod $method): array
    {
        $writes = [];

        foreach (new NodeFinder()->findInstanceOf($method->stmts ?? [], Expr::class) as $assignment) {
            if (!$assignment instanceof Assign && !$assignment instanceof AssignOp && !$assignment instanceof AssignRef) {
                continue;
            }

            $propertyName = $this->resolveOwnPropertyName($assignment->var);

            if ($propertyName !== null) {
                $writes[] = [$propertyName, $assignment->getStartLine()];
            }
        }

        return $writes;
    }

    private function assignedOutsideConstructorError(string $className, string $propertyName, string $methodName, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Dependency property %s::$%s is assigned in %s(). Dependencies must be injected through the constructor'
                . ' and never replaced (no setter or property injection).',
                $className,
                $propertyName,
                $methodName,
            ),
        )
            ->identifier(self::DEPENDENCY_ASSIGNED_OUTSIDE_CONSTRUCTOR_IDENTIFIER)
            ->line($line)
            ->build();
    }

    /**
     * The name of the `$this->property` the expression refers to, or null when it refers to something else.
     */
    private function resolveOwnPropertyName(Expr $expr): ?string
    {
        if (!$expr instanceof PropertyFetch
            || !$expr->var instanceof Variable
            || $expr->var->name !== 'this'
            || !$expr->name instanceof Identifier
        ) {
            return null;
        }

        return $expr->name->toString();
    }

}
