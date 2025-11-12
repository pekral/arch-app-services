<?php

declare(strict_types = 1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pekral\Arch\Examples\Actions\User\GetUser;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->getUser = app(GetUser::class);
});

test('get non existing user throws exception', function (): void {
    User::factory()->create();
    
    $this->getUser->handle(['name' => fake()->name(), 'email' => fake()->email()]);
})->throws(ModelNotFoundException::class);

test('get user returns correct user', function (): void {
    $user = User::factory()->create();
    
    $foundUser = $this->getUser->handle(['name' => $user->name, 'email' => $user->email]);
    
    expect($foundUser->id)->toBe($user->id)
        ->and($foundUser->name)->toBe($user->name)
        ->and($foundUser->email)->toBe($user->email);
});
