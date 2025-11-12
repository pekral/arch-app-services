<?php

declare(strict_types = 1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pekral\Arch\Examples\Actions\User\GetUser;
use Pekral\Arch\Tests\Models\User;

test('get non existing user throws exception', function (): void {
    $getUser = app(GetUser::class);
    User::factory()->create();
    
    $getUser->handle(['name' => fake()->name(), 'email' => fake()->email()]);
})->throws(ModelNotFoundException::class);

test('get user returns correct user', function (): void {
    $getUser = app(GetUser::class);
    $user = User::factory()->create();
    
    $foundUser = $getUser->handle(['name' => $user->name, 'email' => $user->email]);
    
    expect($foundUser->id)->toBe($user->id)
        ->and($foundUser->name)->toBe($user->name)
        ->and($foundUser->email)->toBe($user->email);
});
