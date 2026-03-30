<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\TransferUserDataAction;
use Pekral\Arch\Tests\Models\User;

test('transfer user data updates both users within a transaction', function (): void {
    $fromUser = User::factory()->create(['name' => 'From User']);
    $toUser = User::factory()->create(['name' => 'To User']);
    $action = app(TransferUserDataAction::class);

    $updated = ($action)($fromUser, $toUser, 'New Name');

    expect($updated)->toBeInstanceOf(User::class)
        ->and($updated->name)->toBe('New Name')
        ->and(User::query()->find($fromUser->id)->name)->toBe('transferred');
});
