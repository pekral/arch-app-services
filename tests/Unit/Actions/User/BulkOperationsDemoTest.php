<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\BulkOperationsDemo;
use Pekral\Arch\Tests\Models\User;

test('execute performs bulk operations correctly', function (): void {
    $action = app(BulkOperationsDemo::class);

    $result = $action->execute();

    expect($result['bulk_create_result'])->toBe(3)
        ->and($result['insert_or_ignore_result'])->toBe(3)
        ->and($result['bulk_update_result'])->toBe(5)
        ->and($result['final_user_count'])->toBe(5)
        ->and(User::query()->where('name', 'Alice Johnson (Updated)')->exists())->toBeTrue()
        ->and(User::query()->where('name', 'Bob Smith (Updated)')->exists())->toBeTrue()
        ->and(User::query()->where('name', 'Charlie Brown (Updated)')->exists())->toBeTrue();
});
