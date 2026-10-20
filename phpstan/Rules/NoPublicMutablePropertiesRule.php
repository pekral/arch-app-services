<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Pekral\Arch\PHPStan\Support\ArchitectureLayerMatcher;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids public properties that can be modified after construction in architecture-layer classes
 * (Action, Service, Repository, Model Manager). An architecture object owns its state; it is not a bag
 * of properties other code can rewrite.
 *
 * Allowed: public readonly properties (a readonly class makes every property readonly), public
 * properties with a restricted setter (`public private(set)` / `public protected(set)`), and non-public
 * mutable state. Static properties are left to NoMutableStaticStateRule.
 *
 * Only the four architecture layers are checked, so DTOs, Eloquent models and other data classes are
 * never reported. Further classes (e.g. value objects) are excluded through `$excludedClasses`.
 *
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\ClassPropertyNode>
 */
final readonly class NoPublicMutablePropertiesRule implements Rule
{

    public const string IDENTIFIER = 'arch.publicMutableProperty';

    /**
     * @param list<string> $excludedClasses Classes or interfaces whose implementations are not checked (e.g. a value
     *                                      object marker interface), or namespace prefixes ending with `\`
     *                                      (e.g. `App\ValueObjects\`).
     */
    public function __construct(private array $excludedClasses = [])
    {
    }

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    /**
     * @param \PHPStan\Node\ClassPropertyNode $node
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if (!$this->isPublicMutable($node) || !$this->isCheckedClass($classReflection)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Public property %s::$%s can be modified after construction. Architecture classes must not expose'
                    . ' mutable state: make it readonly, restrict its setter with private(set), or make it non-public.',
                    $classReflection->getName(),
                    $node->getName(),
                ),
            )
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

    private function isPublicMutable(ClassPropertyNode $property): bool
    {
        return $property->isPublic()
            && !$property->isStatic()
            && !$property->isReadOnly()
            && !$property->isPrivateSet()
            && !$property->isProtectedSet();
    }

    private function isCheckedClass(ClassReflection $classReflection): bool
    {
        return ArchitectureLayerMatcher::isArchitectureClass($classReflection)
            && !$this->isExcluded($classReflection);
    }

    private function isExcluded(ClassReflection $classReflection): bool
    {
        return array_any(
            $this->excludedClasses,
            static fn (string $excluded): bool => str_ends_with($excluded, '\\')
                ? str_starts_with($classReflection->getName(), $excluded)
                : $classReflection->is($excluded),
        );
    }

}
