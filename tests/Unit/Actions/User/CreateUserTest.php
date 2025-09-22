<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Acitons\User\CreateUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function fake;

final class CreateUserTest extends TestCase
{

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUser(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        $name = fake()->name();
        $email = fake()->email();
        $password = fake()->password();

        // Act
        $createUserAction($name, $email, $password);

        // Assert
        User::query()->where(['email' => $email, 'name' => $name])
            ->firstOrFail();
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('userDataProvider')]
    public function testCreateUserWithDifferentData(string $email, string $expectedEmail, string $expectedName, string $name, string $password): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);

        // Act
        $user = $createUserAction($name, $email, $password);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($expectedName, $user->name);
        $this->assertSame($expectedEmail, $user->email);
        $this->assertSame($password, $user->password);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithEmptyName(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);

        // Act
        $user = $createUserAction('', 'test@example.com', 'password123');

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('', $user->name);
        $this->assertSame('test@example.com', $user->email);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithEmptyEmail(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);

        // Act
        $user = $createUserAction('John Doe', '', 'password123');

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('', $user->email);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithVeryLongName(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        
        $longName = str_repeat('A', 1_000);
        $email = 'test@example.com';
        $password = 'password123';

        // Act
        $user = $createUserAction($longName, $email, $password);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($longName, $user->name);
        $this->assertSame($email, $user->email);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithVeryLongEmail(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        
        $name = 'John Doe';
        $longEmail = str_repeat('a', 1_00) . '@example.com';
        $password = 'password123';

        // Act
        $user = $createUserAction($name, $longEmail, $password);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($name, $user->name);
        $this->assertSame($longEmail, $user->email);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithSpecialCharactersInEmail(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        
        $name = 'Test User';
        $email = 'test+tag@example-domain.co.uk';
        $password = 'password123';

        // Act
        $user = $createUserAction($name, $email, $password);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($name, $user->name);
        $this->assertSame($email, $user->email);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithNumericName(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        
        $name = '123456';
        $email = 'test@example.com';
        $password = 'password123';

        // Act
        $user = $createUserAction($name, $email, $password);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($name, $user->name);
        $this->assertSame($email, $user->email);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithUnicodeCharacters(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        
        $name = '测试用户';
        $email = 'TEST@EXAMPLE.COM';
        $password = 'password123';

        // Act
        $user = $createUserAction($name, $email, $password);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($name, $user->name);
        $this->assertSame('test@example.com', $user->email);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithWhitespaceInName(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        
        $name = '  john   doe  ';
        $email = 'test@example.com';
        $password = 'password123';

        // Act
        $user = $createUserAction($name, $email, $password);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('  john   doe  ', $user->name);
        $this->assertSame($email, $user->email);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithWhitespaceInEmail(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        
        $name = 'John Doe';
        $email = '  TEST@EXAMPLE.COM  ';
        $password = 'password123';

        // Act
        $user = $createUserAction($name, $email, $password);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($name, $user->name);
        $this->assertSame('  test@example.com  ', $user->email);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUserWithMultipleUsers(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        
        $users = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password1'],
            ['name' => 'Jane Smith', 'email' => 'JANE@EXAMPLE.COM', 'password' => 'password2'],
            ['name' => 'bob wilson', 'email' => 'Bob.Wilson@EXAMPLE.COM', 'password' => 'password3'],
        ];

        // Act
        $createdUsers = [];

        foreach ($users as $userData) {
            $createdUsers[] = $createUserAction($userData['name'], $userData['email'], $userData['password']);
        }

        // Assert
        $this->assertCount(3, $createdUsers);
        
        $this->assertSame('John Doe', $createdUsers[0]->name);
        $this->assertSame('john@example.com', $createdUsers[0]->email);
        
        $this->assertSame('Jane Smith', $createdUsers[1]->name);
        $this->assertSame('jane@example.com', $createdUsers[1]->email);
        
        $this->assertSame('Bob wilson', $createdUsers[2]->name);
        $this->assertSame('bob.wilson@example.com', $createdUsers[2]->email);
    }

    /**
     * @return \Iterator<string, array{string, string, string, string, string}>
     */
    public static function userDataProvider(): \Iterator
    {
        yield 'lowercase email and name' => [
            'JOHN.DOE@EXAMPLE.COM',
            'john.doe@example.com',
            'John doe',
            'john doe',
            'password123',
        ];

        yield 'mixed case email and name' => [
            'Jane.Smith@EXAMPLE.COM',
            'jane.smith@example.com',
            'Jane SMITH',
            'jane SMITH',
            'securePassword',
        ];

        yield 'already formatted data' => [
            'alice.johnson@example.com',
            'alice.johnson@example.com',
            'Alice Johnson',
            'Alice Johnson',
            'myPassword',
        ];

        yield 'special characters in name' => [
            'JOSE.MARIA@EXAMPLE.COM',
            'jose.maria@example.com',
            'José maría',
            'josé maría',
            'password123',
        ];
    }

}
