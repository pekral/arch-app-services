<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\DeleteUser;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->deleteUser = app(DeleteUser::class);
});

test('delete by model removes user', function (): void {
    $user = User::factory()->create();
    
    $this->deleteUser->handle($user);
    
    expect(User::query()->find($user->id))->toBeNull();
});

test('delete by params removes user', function (): void {
    $user = User::factory()->create();
    
    $this->deleteUser->handle($user->id);
    
    expect(User::query()->find($user->id))->toBeNull();
});
