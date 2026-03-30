<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\CreateUserWithTransaction;
use Pekral\Arch\Tests\Models\User;

test('create user with transaction creates both users atomically', function (): void {
    $action = app(CreateUserWithTransaction::class);
    $primaryData = ['name' => 'Primary', 'email' => 'primary@example.com', 'password' => 'secret'];
    $secondaryData = ['name' => 'Secondary', 'email' => 'secondary@example.com', 'password' => 'secret'];

    $result = ($action)($primaryData, $secondaryData);

    expect($result->primary)->toBeInstanceOf(User::class)
        ->and($result->secondary)->toBeInstanceOf(User::class)
        ->and($result->primary->name)->toBe('Primary')
        ->and($result->secondary->name)->toBe('Secondary')
        ->and(User::query()->where('email', 'primary@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'secondary@example.com')->exists())->toBeTrue();
});
