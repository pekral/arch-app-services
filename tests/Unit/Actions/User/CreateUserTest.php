<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Pekral\Arch\Examples\Actions\User\CreateUser;
use Pekral\Arch\Tests\Models\User;

test('create user with invalid data throws validation exception', function (): void {
    $createUserAction = app(CreateUser::class);
    $data = [
        'email' => 'xxx',
        'name' => 123,
    ];

    $createUserAction->execute($data);
})->throws(ValidationException::class);

test('create user successfully creates user', function (): void {
    Notification::fake();
    $createUserAction = app(CreateUser::class);
    $data = [
        'email' => fake()->email(),
        'name' => 'Petr',
        'password' => fake()->password(),
    ];

    $createUserAction->execute($data);

    $model = User::query()->where(['email' => $data['email']])->firstOrFail();
    expect($model->name)->toBe('Petr');
});
