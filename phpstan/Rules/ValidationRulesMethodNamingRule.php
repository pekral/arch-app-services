<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
final class ValidationRulesMethodNamingRule implements Rule
{

    private const string VALIDATION_RULES_INTERFACE = 'Pekral\Arch\DataValidation\ValidationRules';

    private const string REQUIRED_SUFFIX = 'Rules';

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

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        if (!$this->implementsValidationRules($classReflection)) {
            return [];
        }

        return $this->validateMethods($node->getMethods(), $classReflection);
    }

    private function implementsValidationRules(ClassReflection $classReflection): bool
    {
        foreach ($classReflection->getInterfaces() as $interface) {
            if ($interface->getName() === self::VALIDATION_RULES_INTERFACE) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<\PhpParser\Node\Stmt\ClassMethod> $methods
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function validateMethods(array $methods, ClassReflection $classReflection): array
    {
        $errors = [];

        foreach ($methods as $method) {
            $methodErrors = $this->validateMethod($method, $classReflection->getName());
            $errors = [...$errors, ...$methodErrors];
        }

        return $errors;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function validateMethod(ClassMethod $method, string $className): array
    {
        $methodName = $method->name->toString();

        if ($methodName === '__construct') {
            return [$this->createConstructorError($className)];
        }

        if (!$method->isStatic()) {
            return [$this->createNonStaticError($className, $methodName)];
        }

        if (!str_ends_with($methodName, self::REQUIRED_SUFFIX)) {
            return [$this->createNamingError($className, $methodName)];
        }

        return [];
    }

    private function createNamingError(string $className, string $methodName): RuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Method "%s::%s()" in ValidationRules class must end with "%s" suffix.',
                $className,
                $methodName,
                self::REQUIRED_SUFFIX,
            ),
        )->build();
    }

    private function createNonStaticError(string $className, string $methodName): RuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Method "%s::%s()" in ValidationRules class must be static.',
                $className,
                $methodName,
            ),
        )->build();
    }

    private function createConstructorError(string $className): RuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'ValidationRules class "%s" must not have a constructor. All methods must be static.',
                $className,
            ),
        )->build();
    }

}
