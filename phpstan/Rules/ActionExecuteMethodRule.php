<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
final class ActionExecuteMethodRule implements Rule
{

    private const string ARCH_ACTION_INTERFACE = 'Pekral\Arch\Action\ArchAction';

    private const string EXECUTE_METHOD = 'execute';

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return [];
        }

        $className = $this->getActionClassName($scope);

        if ($className === null) {
            return [];
        }

        return $this->validatePublicMethods($node, $className);
    }

    private function getActionClassName(Scope $scope): ?string
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        if (!$classReflection->implementsInterface(self::ARCH_ACTION_INTERFACE)) {
            return null;
        }

        return $classReflection->getName();
    }

    /**
     * @return array<int, string>
     */
    private function getPublicMethodNames(Class_ $class): array
    {
        $names = [];

        foreach ($class->stmts as $stmt) {
            if (!$stmt instanceof ClassMethod) {
                continue;
            }

            if (!$stmt->isPublic()) {
                continue;
            }

            $methodName = $stmt->name->toString();

            if ($methodName === '__construct') {
                continue;
            }

            $names[] = $methodName;
        }

        return $names;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function validatePublicMethods(Class_ $classNode, string $className): array
    {
        $publicMethodNames = $this->getPublicMethodNames($classNode);

        if ($publicMethodNames === []) {
            return [$this->missingExecuteError($className)];
        }

        if (count($publicMethodNames) !== 1) {
            return [$this->multiplePublicMethodsError($className, $publicMethodNames)];
        }

        if ($publicMethodNames[0] !== self::EXECUTE_METHOD) {
            return [$this->invalidMethodNameError($className, $publicMethodNames[0])];
        }

        return [];
    }

    private function createError(string $message): RuleError
    {
        return RuleErrorBuilder::message($message)->build();
    }

    private function missingExecuteError(string $className): RuleError
    {
        return $this->createError(
            sprintf('Action class "%s" must declare a public "%s()" method.', $className, self::EXECUTE_METHOD),
        );
    }

    /**
     * @param array<int, string> $publicMethodNames
     */
    private function multiplePublicMethodsError(string $className, array $publicMethodNames): RuleError
    {
        return $this->createError(
            sprintf(
                'Action class "%s" must expose only the public "%s()" method, but found: %s.',
                $className,
                self::EXECUTE_METHOD,
                implode(', ', $publicMethodNames),
            ),
        );
    }

    private function invalidMethodNameError(string $className, string $methodName): RuleError
    {
        return $this->createError(
            sprintf(
                'Action class "%s" must name its only public method "%s()", "%s()" given.',
                $className,
                self::EXECUTE_METHOD,
                $methodName,
            ),
        );
    }

}
