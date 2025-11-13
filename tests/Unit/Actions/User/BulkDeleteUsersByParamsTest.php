<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\BulkDeleteUsersByParams;
use Pekral\Arch\Tests\Models\User;

test('bulk delete by params deletes matching records', function (): void {
    $bulkDeleteAction = app(BulkDeleteUsersByParams::class);
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(3)->create(['name' => 'Jane']);

    $bulkDeleteAction->execute(['name' => 'John']);

    expect(User::query()->where('name', 'John')->count())->toBe(0)
        ->and(User::query()->where('name', 'Jane')->count())->toBe(3);
});

test('bulk delete by params with multiple conditions', function (): void {
    $bulkDeleteAction = app(BulkDeleteUsersByParams::class);
    User::factory()->create(['name' => 'John', 'email' => 'john1@example.com']);
    User::factory()->create(['name' => 'John', 'email' => 'john2@example.com']);
    User::factory()->create(['name' => 'John', 'email' => 'john3@example.com']);
    User::factory()->create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $bulkDeleteAction->execute(['name' => 'John', 'email' => 'john1@example.com']);

    expect(User::query()->where('name', 'John')->where('email', 'john1@example.com')->count())->toBe(0)
        ->and(User::query()->where('name', 'John')->count())->toBe(2);
});

test('bulk delete by params with no matching records does nothing', function (): void {
    $bulkDeleteAction = app(BulkDeleteUsersByParams::class);
    User::factory()->count(3)->create(['name' => 'John']);

    $bulkDeleteAction->execute(['name' => 'NonExistent']);

    expect(User::query()->where('name', 'John')->count())->toBe(3);
});

test('bulk delete by params deletes all records when no conditions', function (): void {
    $bulkDeleteAction = app(BulkDeleteUsersByParams::class);
    User::factory()->count(5)->create();

    $bulkDeleteAction->execute([]);

    expect(User::count())->toBe(0);
});

test('bulk delete by params with empty table does nothing', function (): void {
    $bulkDeleteAction = app(BulkDeleteUsersByParams::class);

    $bulkDeleteAction->execute(['name' => 'John']);

    expect(User::count())->toBe(0);
});
