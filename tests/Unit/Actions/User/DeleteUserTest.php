<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\DeleteUser;
use Pekral\Arch\Tests\Models\User;

test('delete by model removes user', function (): void {
    $deleteUser = app(DeleteUser::class);
    $user = User::factory()->create();
    
    $deleteUser->handle($user);
    
    expect(User::query()->find($user->id))->toBeNull();
});

test('delete by params removes user', function (): void {
    $deleteUser = app(DeleteUser::class);
    $user = User::factory()->create();
    
    $deleteUser->handle($user->id);
    
    expect(User::query()->find($user->id))->toBeNull();
});
