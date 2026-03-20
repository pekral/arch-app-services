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
final class ActionInvokeMethodRule implements Rule
{

    private const string ARCH_ACTION_INTERFACE = 'Pekral\Arch\Action\ArchAction';

    private const string INVOKE_METHOD = '__invoke';

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
            return [$this->missingInvokeError($className)];
        }

        if (count($publicMethodNames) !== 1) {
            return [$this->multiplePublicMethodsError($className, $publicMethodNames)];
        }

        if ($publicMethodNames[0] !== self::INVOKE_METHOD) {
            return [$this->invalidMethodNameError($className, $publicMethodNames[0])];
        }

        return [];
    }

    private function createError(string $message): RuleError
    {
        return RuleErrorBuilder::message($message)->build();
    }

    private function missingInvokeError(string $className): RuleError
    {
        return $this->createError(
            sprintf(
                'Action class "%s" must declare a public "%s()" method and no other public methods.',
                $className,
                self::INVOKE_METHOD,
            ),
        );
    }

    /**
     * @param array<int, string> $publicMethodNames
     */
    private function multiplePublicMethodsError(string $className, array $publicMethodNames): RuleError
    {
        return $this->createError(
            sprintf(
                'Action class "%s" must not declare public methods other than "%s()", but found: %s.',
                $className,
                self::INVOKE_METHOD,
                implode(', ', $publicMethodNames),
            ),
        );
    }

    private function invalidMethodNameError(string $className, string $methodName): RuleError
    {
        return $this->createError(
            sprintf(
                'Action class "%s" must use only public "%s()" as its entry point, "%s()" given.',
                $className,
                self::INVOKE_METHOD,
                $methodName,
            ),
        );
    }

}
