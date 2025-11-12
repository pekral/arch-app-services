<?php

declare(strict_types = 1);

use Pekral\Arch\Exceptions\MassUpdateNotAvailable;

test('missing package creates exception with correct message', function (): void {
    $exception = MassUpdateNotAvailable::missingPackage();

    expect($exception)->toBeInstanceOf(MassUpdateNotAvailable::class)
        ->and($exception->getMessage())->toContain('iksaku/laravel-mass-update')
        ->toContain('composer require');
});

test('trait not used creates exception with correct message', function (): void {
    $modelClass = 'App\Models\User';

    $exception = MassUpdateNotAvailable::traitNotUsed($modelClass);

    expect($exception)->toBeInstanceOf(MassUpdateNotAvailable::class)
        ->and($exception->getMessage())->toContain($modelClass)
        ->toContain('MassUpdatable')
        ->toContain('use Iksaku\Laravel\MassUpdate\MassUpdatable');
});
