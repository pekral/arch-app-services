<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Support;

use PHPStan\Reflection\ClassReflection;

/**
 * Decides whether a class is a Data Validator. A Data Validator is a class whose name ends with `DataValidator`,
 * optionally limited to the namespaces a project configures.
 *
 * Shared by every rule that accepts a `dataValidatorNamespaces` argument, so all of them recognise a Data Validator
 * the same way.
 */
final class DataValidatorClassMatcher
{

    private const string CLASS_SUFFIX = 'DataValidator';

    /**
     * @param list<string> $dataValidatorNamespaces Namespace prefixes (e.g. `App\`) the match is limited to. Empty means
     *                                              every namespace.
     */
    public static function isDataValidator(ClassReflection $classReflection, array $dataValidatorNamespaces): bool
    {
        $className = $classReflection->getName();

        if (!str_ends_with($className, self::CLASS_SUFFIX)) {
            return false;
        }

        return $dataValidatorNamespaces === []
            || array_any(
                $dataValidatorNamespaces,
                static fn (string $namespace): bool => str_starts_with($className, $namespace),
            );
    }

}
