<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
final class ServiceNamingConventionRule implements Rule
{

    private const string BASE_MODEL_SERVICE_CLASS = 'Pekral\Arch\Service\BaseModelService';

    private const string REQUIRED_SUFFIX = 'ModelService';

    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return [];
        }

        if ($node->extends === null) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        $className = $classReflection->getName();

        if ($className === self::BASE_MODEL_SERVICE_CLASS) {
            return [];
        }

        if (!$this->extendsBaseModelService($node->extends, $scope)) {
            return [];
        }

        if ($this->hasCorrectSuffix($className)) {
            return [];
        }

        return [$this->createErrorMessage($className)];
    }

    private function createErrorMessage(string $className): RuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Service class "%s" extending BaseModelService must end with "%s".',
                $className,
                self::REQUIRED_SUFFIX,
            ),
        )->build();
    }

    private function extendsBaseModelService(Node\Name $extends, Scope $scope): bool
    {
        $resolvedName = $scope->resolveName($extends);

        if ($resolvedName === self::BASE_MODEL_SERVICE_CLASS) {
            return true;
        }

        $type = $scope->resolveTypeByName($extends);

        if ($type->getObjectClassNames() === []) {
            return false;
        }

        return in_array(self::BASE_MODEL_SERVICE_CLASS, $type->getObjectClassNames(), true);
    }

    private function hasCorrectSuffix(string $className): bool
    {
        return str_ends_with($className, self::REQUIRED_SUFFIX);
    }

}
