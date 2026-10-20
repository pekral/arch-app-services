<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Support;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\ModelManager\ModelManager;
use Pekral\Arch\Repository\Repository;
use Pekral\Arch\Service\BaseModelService;
use PHPStan\Reflection\ClassReflection;

/**
 * Decides whether a class belongs to one of the package architecture layers: an Action (implements
 * ArchAction), a Service (extends BaseModelService), a Repository (implements Repository) or a Model
 * Manager (implements ModelManager). The decision uses type relationships, never class-name suffixes.
 *
 * Shared by every rule that guards the state and construction of architecture-layer objects, so all
 * of them recognise the same layers.
 */
final class ArchitectureLayerMatcher
{

    private const array LAYER_TYPES = [
        ArchAction::class,
        BaseModelService::class,
        Repository::class,
        ModelManager::class,
    ];

    public static function isArchitectureClass(ClassReflection $classReflection): bool
    {
        if (!$classReflection->isClass()) {
            return false;
        }

        return array_any(
            self::LAYER_TYPES,
            static fn (string $layerType): bool => $classReflection->is($layerType),
        );
    }

}
