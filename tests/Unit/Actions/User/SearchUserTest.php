<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\SearchUser;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->searchUser = app(SearchUser::class);
});

test('search user finds existing user', function (): void {
    $user = User::factory()->create();
    
    $foundUser = $this->searchUser->handle(['name' => $user->name, 'email' => $user->email]);
    
    expect($foundUser)->not->toBeNull()
        ->and($foundUser->id)->toBe($user->id)
        ->and($foundUser->name)->toBe($user->name)
        ->and($foundUser->email)->toBe($user->email);
});

test('search non existing user returns null', function (): void {
    User::factory()->create();
    
    $foundUser = $this->searchUser->handle(['name' => fake()->name(), 'email' => fake()->email()]);
    
    expect($foundUser)->toBeNull();
});
