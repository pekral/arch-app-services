<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\Errors\DuplicateEmail;
use Pekral\Arch\Examples\Actions\User\Errors\UserNotFound;
use Pekral\Arch\Examples\Actions\User\Errors\ValidationFailed;
use Pekral\Arch\Examples\Actions\User\UpdateUserWithResult;
use Pekral\Arch\Tests\Models\User;

test('execute returns success when user is updated', function (): void {
    $user = User::factory()->create(['name' => 'Old Name']);
    $action = app(UpdateUserWithResult::class);

    $result = $action->execute($user->id, ['name' => 'New Name']);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value()->id)->toBe($user->id);

    $user->refresh();
    expect($user->name)->toBe('New name');
});

test('execute returns not found error when user does not exist', function (): void {
    $action = app(UpdateUserWithResult::class);

    $result = $action->execute(999999, ['name' => 'New Name']);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeInstanceOf(UserNotFound::class);
});

test('execute returns validation error when email is invalid', function (): void {
    $user = User::factory()->create();
    $action = app(UpdateUserWithResult::class);

    $result = $action->execute($user->id, ['email' => 'invalid-email']);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeInstanceOf(ValidationFailed::class);
});

test('execute returns duplicate email error when new email exists', function (): void {
    $existingEmail = fake()->unique()->email();
    User::factory()->create(['email' => $existingEmail]);
    $user = User::factory()->create(['email' => fake()->unique()->email()]);

    $action = app(UpdateUserWithResult::class);

    $result = $action->execute($user->id, ['email' => $existingEmail]);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeInstanceOf(DuplicateEmail::class);
});

test('execute allows same email when unchanged', function (): void {
    $email = fake()->unique()->email();
    $user = User::factory()->create(['email' => $email]);

    $action = app(UpdateUserWithResult::class);

    $result = $action->execute($user->id, ['email' => $email, 'name' => 'New Name']);

    expect($result->isSuccess())->toBeTrue();
});

test('execute normalizes updated name', function (): void {
    $user = User::factory()->create(['name' => 'Old Name']);
    $action = app(UpdateUserWithResult::class);

    $result = $action->execute($user->id, ['name' => 'peter']);

    expect($result->isSuccess())->toBeTrue();

    $user->refresh();
    expect($user->name)->toBe('Peter');
});
