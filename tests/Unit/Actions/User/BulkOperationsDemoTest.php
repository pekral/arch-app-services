<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\BulkOperationsDemo;
use Pekral\Arch\Tests\Models\User;

test('execute performs bulk operations correctly', function (): void {
    $action = app(BulkOperationsDemo::class);
    $updateData = [
        ['id' => 1, 'name' => 'Alice Johnson (Updated)'],
        ['id' => 2, 'name' => 'Bob Smith (Updated)'],
        ['id' => 3, 'name' => 'Charlie Brown (Updated)'],
    ];

    $result = ($action)($updateData);

    expect($result->bulkCreateResult)->toBe(3)
        ->and($result->insertOrIgnoreResult)->toBe(3)
        ->and($result->bulkUpdateResult)->toBe(3)
        ->and($result->finalUserCount)->toBe(5)
        ->and(User::query()->where('name', 'Alice Johnson (Updated)')->exists())->toBeTrue()
        ->and(User::query()->where('name', 'Bob Smith (Updated)')->exists())->toBeTrue()
        ->and(User::query()->where('name', 'Charlie Brown (Updated)')->exists())->toBeTrue();
});
