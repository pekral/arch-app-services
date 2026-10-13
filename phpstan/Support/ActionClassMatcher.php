<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Support;

use Pekral\Arch\Action\ArchAction;
use PHPStan\Reflection\ClassReflection;

/**
 * Decides whether a class is an Action. A class is an Action when it implements ArchAction, or when it
 * lives in one of the namespaces a project configures for Actions that do not implement ArchAction yet.
 *
 * Shared by every rule that accepts an `actionNamespaces` argument, so all of them recognise an Action
 * the same way.
 */
final class ActionClassMatcher
{

    /**
     * @param list<string> $actionNamespaces Namespace prefixes (e.g. `App\Actions\`) whose classes count as Actions.
     */
    public static function isAction(ClassReflection $classReflection, array $actionNamespaces): bool
    {
        return $classReflection->implementsInterface(ArchAction::class)
            || self::findNamespace($classReflection, $actionNamespaces) !== null;
    }

    /**
     * The first configured namespace prefix the class lives in, or null when it lives in none of them.
     *
     * @param list<string> $actionNamespaces Namespace prefixes (e.g. `App\Actions\`) that hold Actions.
     */
    public static function findNamespace(ClassReflection $classReflection, array $actionNamespaces): ?string
    {
        return array_find(
            $actionNamespaces,
            static fn (string $actionNamespace): bool => str_starts_with($classReflection->getName(), $actionNamespace),
        );
    }

}
