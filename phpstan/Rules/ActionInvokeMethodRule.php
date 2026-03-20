<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces that every Action class is declared `final readonly` and exposes
 * exactly one public business entry point — `__invoke()` — with an explicit
 * return type. Constructor is excluded from the public-method count.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
final readonly class ActionInvokeMethodRule implements Rule
{

    private const string ARCH_ACTION_INTERFACE = 'Pekral\Arch\Action\ArchAction';

    private const string INVOKE_METHOD = '__invoke';

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

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

        $className = $this->resolveActionClassName($node, $scope);

        if ($className === null) {
            return [];
        }

        $errors = [];

        if (!$node->isFinal()) {
            $errors[] = $this->notFinalError($className);
        }

        if (!$node->isReadonly()) {
            $errors[] = $this->notReadonlyError($className);
        }

        $publicMethodErrors = $this->validatePublicMethods($node, $className);

        return [...$errors, ...$publicMethodErrors];
    }

    /**
     * Resolves the fully-qualified class name and returns it only when the
     * class implements ArchAction; returns null otherwise.
     */
    private function resolveActionClassName(Class_ $node, Scope $scope): ?string
    {
        if ($node->name === null) {
            return null;
        }

        $className = isset($node->namespacedName)
            ? $node->namespacedName->toString()
            : $scope->getNamespace() . '\\' . $node->name->toString();

        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (!$classReflection->implementsInterface(self::ARCH_ACTION_INTERFACE)) {
            return null;
        }

        return $className;
    }

    /**
     * Returns all public method names excluding __construct.
     *
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

    private function getInvokeMethod(Class_ $class): ?ClassMethod
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->name->toString() === self::INVOKE_METHOD) {
                return $stmt;
            }
        }

        return null;
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

        $invokeMethod = $this->getInvokeMethod($classNode);

        if ($invokeMethod !== null && $invokeMethod->returnType === null) {
            return [$this->missingReturnTypeError($className)];
        }

        return [];
    }

    private function createError(string $message): RuleError
    {
        return RuleErrorBuilder::message($message)->build();
    }

    private function notFinalError(string $className): RuleError
    {
        return $this->createError(
            sprintf(
                'Action class "%s" must be declared as "final".',
                $className,
            ),
        );
    }

    private function notReadonlyError(string $className): RuleError
    {
        return $this->createError(
            sprintf(
                'Action class "%s" must be declared as "readonly".',
                $className,
            ),
        );
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

    private function missingReturnTypeError(string $className): RuleError
    {
        return $this->createError(
            sprintf(
                'Action class "%s" must declare an explicit return type on "%s()".',
                $className,
                self::INVOKE_METHOD,
            ),
        );
    }

}
