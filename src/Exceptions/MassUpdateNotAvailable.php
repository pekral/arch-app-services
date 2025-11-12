<?php

declare(strict_types = 1);

namespace Pekral\Arch\Exceptions;

use RuntimeException;

final class MassUpdateNotAvailable extends RuntimeException
{

    public static function missingPackage(): self
    {
        return new self(
            'Mass update functionality requires the iksaku/laravel-mass-update package. '
            . 'Install it with: composer require iksaku/laravel-mass-update',
        );
    }

    public static function traitNotUsed(string $modelClass): self
    {
        return new self(
            sprintf(
                'Model %s must use the MassUpdatable trait from iksaku/laravel-mass-update package. '
                . 'Add "use Iksaku\Laravel\MassUpdate\MassUpdatable;" to your model class.',
                $modelClass,
            ),
        );
    }

}
