<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Notification;
use Pekral\Arch\Examples\Actions\User\CreateUserWithResult;
use Pekral\Arch\Examples\Actions\User\Errors\UserError;
use Pekral\Arch\Tests\Models\User;

test('create user with result returns failure for missing email', function (): void {
    $action = app(CreateUserWithResult::class);
    $data = ['name' => 'Petr'];

    $result = ($action)($data);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(UserError::INVALID_DATA);
});

test('create user with result returns failure for non-string email', function (): void {
    $action = app(CreateUserWithResult::class);
    $data = ['email' => 123, 'name' => 'Petr'];

    $result = ($action)($data);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(UserError::INVALID_DATA);
});

test('create user with result returns failure for duplicate email', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);
    $action = app(CreateUserWithResult::class);
    $data = ['email' => 'existing@example.com', 'name' => 'Petr', 'password' => 'password123'];

    $result = ($action)($data);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(UserError::EMAIL_ALREADY_EXISTS);
});

test('create user with result returns success for valid data', function (): void {
    Notification::fake();
    $action = app(CreateUserWithResult::class);
    $data = ['email' => 'new@example.com', 'name' => 'petr', 'password' => 'password123'];

    $result = ($action)($data);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->unwrap())->toBeInstanceOf(User::class)
        ->and($result->unwrap()->email)->toBe('new@example.com')
        ->and($result->unwrap()->name)->toBe('Petr');
});
