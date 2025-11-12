<?php

declare(strict_types = 1);

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Examples\Actions\User\UpdateUser;
use Pekral\Arch\Tests\Models\User;

test('update user with valid data', function (): void {
    $user = User::factory()->create([
        'email' => 'OLD@EXAMPLE.COM',
        'name' => 'old name',
    ]);
    $updateUserAction = app(UpdateUser::class);
    $data = [
        'email' => 'NEW@EXAMPLE.COM',
        'name' => 'new name',
    ];

    $result = $updateUserAction->execute($user, $data);

    expect($result)->toBe($user);
    
    $user->refresh();
    expect($user->email)->toBe('new@example.com')
        ->and($user->name)->toBe('New name');
});

test('update user with invalid email throws exception', function (): void {
    $user = User::factory()->create();
    $updateUserAction = app(UpdateUser::class);
    $data = [
        'email' => 'invalid-email',
        'name' => 'Test Name',
    ];

    $updateUserAction->execute($user, $data);
})->throws(ValidationException::class);

test('update user with missing email throws exception', function (): void {
    $user = User::factory()->create();
    $updateUserAction = app(UpdateUser::class);
    $data = [
        'name' => 'Test Name',
    ];

    $updateUserAction->execute($user, $data);
})->throws(ValidationException::class);

test('update user with missing name throws exception', function (): void {
    $user = User::factory()->create();
    $updateUserAction = app(UpdateUser::class);
    $data = [
        'email' => fake()->email(),
    ];

    $updateUserAction->execute($user, $data);
})->throws(ValidationException::class);

test('update user transforms email to lowercase', function (): void {
    $user = User::factory()->create();
    $updateUserAction = app(UpdateUser::class);
    $data = [
        'email' => 'UPPERCASE@EXAMPLE.COM',
        'name' => 'Test',
    ];

    $updateUserAction->execute($user, $data);

    $user->refresh();
    expect($user->email)->toBe('uppercase@example.com');
});

test('update user transforms name to ucfirst', function (): void {
    $user = User::factory()->create();
    $updateUserAction = app(UpdateUser::class);
    $data = [
        'email' => fake()->email(),
        'name' => 'lowercase name',
    ];

    $updateUserAction->execute($user, $data);

    $user->refresh();
    expect($user->name)->toBe('Lowercase name');
});

test('update user with empty data throws exception', function (): void {
    $user = User::factory()->create();
    $updateUserAction = app(UpdateUser::class);
    $data = [];

    $updateUserAction->execute($user, $data);
})->throws(ValidationException::class);
