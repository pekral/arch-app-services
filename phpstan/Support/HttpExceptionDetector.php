<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Support;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Detects an expression that produces an HTTP response exception: `throw` of, and `throw_if()` /
 * `throw_unless()` with, a Symfony HttpExceptionInterface or an Illuminate HttpResponseException
 * (as a class-string or as an instance), and every call of `abort()`, `abort_if()` and `abort_unless()`.
 *
 * Shared by every rule that forbids HTTP exceptions in a layer, so all of them report the same constructs
 * with the same message.
 */
final class HttpExceptionDetector
{

    private const array ABORT_FUNCTIONS = ['abort', 'abort_if', 'abort_unless'];

    private const array THROW_FUNCTIONS = ['throw_if', 'throw_unless'];

    /**
     * The HTTP exception class or the abort function the expression produces, or null when it produces none.
     */
    public static function detect(Expr $node, Scope $scope, ReflectionProvider $reflectionProvider): ?string
    {
        if ($node instanceof Throw_) {
            return self::resolveHttpException($node->expr, $scope, $reflectionProvider);
        }

        if (!$node instanceof FuncCall || !$node->name instanceof Name) {
            return null;
        }

        $functionName = $node->name->toLowerString();

        if (in_array($functionName, self::ABORT_FUNCTIONS, strict: true)) {
            return $functionName . '()';
        }

        if (!in_array($functionName, self::THROW_FUNCTIONS, strict: true)) {
            return null;
        }

        $arguments = $node->getArgs();

        if (!isset($arguments[1])) {
            return null;
        }

        return self::resolveHttpException($arguments[1]->value, $scope, $reflectionProvider);
    }

    public static function message(string $layer, string $violation): string
    {
        return sprintf(
            '%s must not produce an HTTP response exception (%s). Throw a domain exception such as %s instead'
            . ' and let the entry point translate it into an HTTP response.',
            $layer,
            $violation,
            ModelNotFoundException::class,
        );
    }

    private static function resolveHttpException(Expr $expression, Scope $scope, ReflectionProvider $reflectionProvider): ?string
    {
        $type = $scope->getType($expression);

        foreach ($type->getConstantStrings() as $constantString) {
            $matched = self::matchHttpException($constantString->getValue(), $reflectionProvider);

            if ($matched !== null) {
                return $matched;
            }
        }

        foreach ($type->getObjectClassNames() as $objectClassName) {
            $matched = self::matchHttpException($objectClassName, $reflectionProvider);

            if ($matched !== null) {
                return $matched;
            }
        }

        return null;
    }

    private static function matchHttpException(string $className, ReflectionProvider $reflectionProvider): ?string
    {
        if (!$reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $reflectionProvider->getClass($className);

        if ($classReflection->is(HttpExceptionInterface::class) || $classReflection->is(HttpResponseException::class)) {
            return $classReflection->getName();
        }

        return null;
    }

}
