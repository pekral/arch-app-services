<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\New_>
 */
final class ValidationRulesNoInstantiationRule implements Rule
{

    private const string VALIDATION_RULES_INTERFACE = 'Pekral\Arch\DataValidation\ValidationRules';

    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof New_) {
            return [];
        }

        if (!$node->class instanceof Name) {
            return [];
        }

        $className = $scope->resolveName($node->class);
        $classType = new ObjectType($className);
        $validationRulesType = new ObjectType(self::VALIDATION_RULES_INTERFACE);

        if (!$validationRulesType->isSuperTypeOf($classType)->yes()) {
            return [];
        }

        return [$this->createError($className)];
    }

    private function createError(string $className): RuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'ValidationRules class "%s" must not be instantiated. Use static methods instead.',
                $className,
            ),
        )->build();
    }

}
