<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Pekral\Arch\Action\ArchAction;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces that every concrete Action class is declared `final readonly`. A final Action cannot be changed
 * through inheritance and a readonly Action cannot replace its dependencies after construction.
 *
 * Only concrete named classes implementing ArchAction are checked. Interfaces, traits, enums, abstract
 * support classes and anonymous classes are not reported.
 *
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\InClassNode>
 */
final readonly class ActionClassModifiersRule implements Rule
{

    public const string NOT_FINAL_IDENTIFIER = 'arch.actionNotFinal';

    public const string NOT_READONLY_IDENTIFIER = 'arch.actionNotReadonly';

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param \PHPStan\Node\InClassNode $node
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if (!$this->isConcreteAction($classReflection)) {
            return [];
        }

        $errors = [];

        if (!$classReflection->isFinalByKeyword()) {
            $errors[] = $this->createError($classReflection, 'final', self::NOT_FINAL_IDENTIFIER);
        }

        if (!$classReflection->isReadOnly()) {
            $errors[] = $this->createError($classReflection, 'readonly', self::NOT_READONLY_IDENTIFIER);
        }

        return $errors;
    }

    private function isConcreteAction(ClassReflection $classReflection): bool
    {
        return $classReflection->isClass()
            && !$classReflection->isAbstract()
            && !$classReflection->isAnonymous()
            && $classReflection->implementsInterface(ArchAction::class);
    }

    private function createError(ClassReflection $classReflection, string $modifier, string $identifier): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Action class "%s" must be declared as "%s".',
                $classReflection->getName(),
                $modifier,
            ),
        )
            ->identifier($identifier)
            ->build();
    }

}
