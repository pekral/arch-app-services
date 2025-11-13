<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\DeleteUserByModelManager;
use Pekral\Arch\Tests\Models\User;

test('delete by model manager removes user', function (): void {
    $deleteUser = app(DeleteUserByModelManager::class);
    $user = User::factory()->create();

    $result = $deleteUser->handle($user);

    expect($result)->toBeTrue()
        ->and(User::query()->find($user->id))->toBeNull();
});
