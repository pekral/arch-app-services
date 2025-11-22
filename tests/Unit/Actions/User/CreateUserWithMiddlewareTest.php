<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Pekral\Arch\Action\ActionExecutor;
use Pekral\Arch\Examples\Actions\User\CreateUserWithMiddleware;
use Pekral\Arch\Tests\Models\User;

test('create user with middleware uses logging', function (): void {
    Notification::fake();
    Log::spy();
    $action = app(CreateUserWithMiddleware::class);
    $executor = app(ActionExecutor::class);
    $data = [
        'email' => fake()->email(),
        'name' => 'Petr',
        'password' => fake()->password(),
    ];

    $result = $executor->execute($action, ['data' => $data]);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->name)->toBe('Petr');
    Log::shouldHaveReceived('channel')
        ->twice()
        ->with('stack');
});

test('create user with middleware and invalid data throws validation exception', function (): void {
    $action = app(CreateUserWithMiddleware::class);
    $executor = app(ActionExecutor::class);
    $data = [
        'email' => 'xxx',
        'name' => 123,
    ];

    expect(fn (): mixed => $executor->execute($action, ['data' => $data]))
        ->toThrow(ValidationException::class);
});
