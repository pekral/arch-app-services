<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Notification;
use Pekral\Arch\Examples\Actions\User\CreateUserWithResult;
use Pekral\Arch\Examples\Actions\User\Errors\DuplicateEmail;
use Pekral\Arch\Examples\Actions\User\Errors\ValidationFailed;
use Pekral\Arch\Tests\Models\User;

test('execute returns success result with user when data is valid', function (): void {
    Notification::fake();
    $createUserAction = app(CreateUserWithResult::class);
    $data = [
        'email' => fake()->unique()->email(),
        'name' => 'John',
        'password' => fake()->password(),
    ];

    $result = $createUserAction->execute($data);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBeInstanceOf(User::class)
        ->and($result->value()->name)->toBe('John');
});

test('execute returns validation error when email is missing', function (): void {
    $createUserAction = app(CreateUserWithResult::class);

    $result = $createUserAction->execute(['name' => 'John']);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeInstanceOf(ValidationFailed::class)
        ->and($result->error()->getMessage())->toContain('email');
});

test('execute returns validation error when email is invalid', function (): void {
    $createUserAction = app(CreateUserWithResult::class);

    $result = $createUserAction->execute(['email' => 'not-valid-email', 'name' => 'John']);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeInstanceOf(ValidationFailed::class);
});

test('execute returns validation error when name is too short', function (): void {
    $createUserAction = app(CreateUserWithResult::class);

    $result = $createUserAction->execute(['email' => 'test@example.com', 'name' => 'A']);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeInstanceOf(ValidationFailed::class);
});

test('execute returns duplicate email error when email already exists', function (): void {
    Notification::fake();
    $existingEmail = fake()->unique()->email();
    User::factory()->create(['email' => $existingEmail]);

    $createUserAction = app(CreateUserWithResult::class);

    $result = $createUserAction->execute(['email' => strtoupper($existingEmail), 'name' => 'John']);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeInstanceOf(DuplicateEmail::class)
        ->and($result->error()->getEmail())->toBe(strtolower($existingEmail));
});

test('validation error can be converted to array', function (): void {
    $createUserAction = app(CreateUserWithResult::class);

    $result = $createUserAction->execute([]);

    expect($result->isFailure())->toBeTrue();

    /** @var \Pekral\Arch\Examples\Actions\User\Errors\ValidationFailed $error */
    $error = $result->error();
    $errorArray = $error->toArray();

    expect($errorArray)->toHaveKey('type')
        ->and($errorArray['type'])->toBe('validation')
        ->and($errorArray)->toHaveKey('errors')
        ->and($errorArray)->toHaveKey('message');
});

test('execute normalizes email to lowercase', function (): void {
    Notification::fake();
    $createUserAction = app(CreateUserWithResult::class);

    $result = $createUserAction->execute(['email' => 'TEST@EXAMPLE.COM', 'name' => 'John', 'password' => 'secret']);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value()->email)->toBe('test@example.com');
});

test('execute normalizes name with ucfirst', function (): void {
    Notification::fake();
    $createUserAction = app(CreateUserWithResult::class);

    $result = $createUserAction->execute(['email' => fake()->unique()->email(), 'name' => 'john', 'password' => 'secret']);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value()->name)->toBe('John');
});
