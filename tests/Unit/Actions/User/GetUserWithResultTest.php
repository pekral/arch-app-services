<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\Errors\UserNotFound;
use Pekral\Arch\Examples\Actions\User\GetUserWithResult;
use Pekral\Arch\Tests\Models\User;

test('execute returns success result when user exists', function (): void {
    $user = User::factory()->create();
    $action = app(GetUserWithResult::class);

    $result = $action->execute($user->id);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value()->id)->toBe($user->id);
});

test('execute returns not found error when user does not exist', function (): void {
    $action = app(GetUserWithResult::class);

    $result = $action->execute(999999);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeInstanceOf(UserNotFound::class)
        ->and($result->error()->getIdentifier())->toBe(999999);
});

test('executeByParams returns success when user found by params', function (): void {
    $email = fake()->unique()->email();
    $user = User::factory()->create(['email' => $email]);

    $action = app(GetUserWithResult::class);

    $result = $action->executeByParams(['email' => $email]);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value()->id)->toBe($user->id);
});

test('executeByParams returns not found error when no user matches params', function (): void {
    $action = app(GetUserWithResult::class);

    $result = $action->executeByParams(['email' => 'nonexistent@example.com']);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeInstanceOf(UserNotFound::class);
});

test('not found error can be converted to array', function (): void {
    $action = app(GetUserWithResult::class);

    $result = $action->execute(42);

    expect($result->isFailure())->toBeTrue();

    /** @var \Pekral\Arch\Examples\Actions\User\Errors\UserNotFound $error */
    $error = $result->error();
    $errorArray = $error->toArray();

    expect($errorArray)->toHaveKey('type')
        ->and($errorArray['type'])->toBe('not_found')
        ->and($errorArray['identifier'])->toBe(42);
});
