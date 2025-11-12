<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\BulkImportUsers;
use Pekral\Arch\Tests\Models\User;

test('execute with empty data returns zero results', function (): void {
    $action = app(BulkImportUsers::class);
    $userData = [];

    $result = $action->execute($userData);

    expect($result)->toBe([
        'created' => 0,
        'ignored' => 0,
        'total_processed' => 0,
    ]);
});

test('execute with new users creates all users', function (): void {
    $action = app(BulkImportUsers::class);
    $userData = [
        ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password456'],
    ];

    $result = $action->execute($userData);

    expect($result['total_processed'])->toBe(2)
        ->and($result['created'])->toBe(2)
        ->and($result['ignored'])->toBe(0)
        ->and(User::query()->where('email', 'john@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('execute with mixed data handles existing and new users', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);
    $action = app(BulkImportUsers::class);
    $userData = [
        ['name' => 'Existing User', 'email' => 'existing@example.com', 'password' => 'password123'],
        ['name' => 'New User', 'email' => 'new@example.com', 'password' => 'password456'],
    ];

    $result = $action->execute($userData);

    expect($result['total_processed'])->toBe(2)
        ->and($result['created'])->toBe(1)
        ->and($result['ignored'])->toBe(1)
        ->and(User::query()->where('email', 'new@example.com')->exists())->toBeTrue();
});
