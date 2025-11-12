<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\SearchUser;
use Pekral\Arch\Tests\Models\User;

test('search user finds existing user', function (): void {
    $searchUser = app(SearchUser::class);
    $user = User::factory()->create();
    
    $foundUser = $searchUser->handle(['name' => $user->name, 'email' => $user->email]);
    
    expect($foundUser)->not->toBeNull();
    
    assert($foundUser instanceof User);
    expect($foundUser->id)->toBe($user->id)
        ->and($foundUser->name)->toBe($user->name)
        ->and($foundUser->email)->toBe($user->email);
});

test('search non existing user returns null', function (): void {
    $searchUser = app(SearchUser::class);
    User::factory()->create();
    
    $foundUser = $searchUser->handle(['name' => fake()->name(), 'email' => fake()->email()]);
    
    expect($foundUser)->toBeNull();
});
