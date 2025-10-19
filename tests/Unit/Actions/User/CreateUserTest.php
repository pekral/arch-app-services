<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Pekral\Arch\Examples\Actions\User\CreateUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function assert;
use function fake;

final class CreateUserTest extends TestCase
{

    public function testCreateUserWithInvalidData(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        assert($createUserAction instanceof CreateUser);
        $data = [
            'email' => 'xxx',
            'name' => 123,
        ];

        // Act && assert
        $this->expectException(ValidationException::class);
        $createUserAction->execute($data);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUser(): void
    {
        // Arrange
        Notification::fake();
        $createUserAction = $this->app?->make(CreateUser::class);
        assert($createUserAction instanceof CreateUser);
        $data = [
            'email' => fake()->email(),
            'name' => 'Petr',
            'password' => fake()->password(),
        ];

        // Act
        $createUserAction->execute($data);

        // Assert
        $model = User::query()->where(['email' => $data['email']])
            ->firstOrFail();

        $this->assertEquals('Petr', $model->name);
    }

}
