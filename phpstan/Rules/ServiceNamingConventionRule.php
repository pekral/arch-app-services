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
        if (!$this->isValidClassNode($node)) {
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

        if ($node->extends === null || !$this->extendsBaseModelService($node->extends, $scope)) {
            return [];
        }

        if ($this->hasCorrectSuffix($className)) {
            return [];
        }

        return [$this->createErrorMessage($className)];
    }

    private function isValidClassNode(Node $node): bool
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return false;
        }

        if ($node->name === null) {
            return false;
        }

        return $node->extends !== null;
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
        $className = $scope->resolveName($extends);

        return $className === self::BASE_MODEL_SERVICE_CLASS;
    }

    private function hasCorrectSuffix(string $className): bool
    {
        return str_ends_with($className, self::REQUIRED_SUFFIX);
    }

}
