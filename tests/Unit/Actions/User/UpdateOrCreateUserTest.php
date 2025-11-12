<?php

declare(strict_types = 1);

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Examples\Actions\User\UpdateOrCreateUser;
use Pekral\Arch\Tests\Models\User;

test('update or create user creates new record', function (): void {
    $updateOrCreateUserAction = app(UpdateOrCreateUser::class);
    $attributes = ['email' => 'newuser@example.com'];
    $values = ['name' => 'New User', 'password' => 'password123'];
    
    $result = $updateOrCreateUserAction->execute($attributes, $values);
    
    expect($result)->toBeInstanceOf(User::class)
        ->and($result->email)->toBe('newuser@example.com')
        ->and($result->name)->toBe('New user')
        ->and(User::query()->where('email', 'newuser@example.com')->where('name', 'New user')->exists())->toBeTrue();
});

test('update or create user updates existing record', function (): void {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Original Name',
    ]);
    $updateOrCreateUserAction = app(UpdateOrCreateUser::class);
    $attributes = ['email' => 'existing@example.com'];
    $values = ['name' => 'Updated Name'];
    
    $result = $updateOrCreateUserAction->execute($attributes, $values);
    
    expect($result->id)->toBe($existingUser->id)
        ->and($result->email)->toBe('existing@example.com')
        ->and($result->name)->toBe('Updated name');
    
    $existingUser->refresh();
    expect($existingUser->name)->toBe('Updated name');
});

test('update or create user with invalid email', function (): void {
    $updateOrCreateUserAction = app(UpdateOrCreateUser::class);
    $attributes = ['email' => 'invalid-email'];
    $values = ['name' => 'Test Name'];
    
    $updateOrCreateUserAction->execute($attributes, $values);
})->throws(ValidationException::class);

test('update or create user with missing email', function (): void {
    $updateOrCreateUserAction = app(UpdateOrCreateUser::class);
    $attributes = [];
    $values = ['name' => 'Test Name'];
    
    $updateOrCreateUserAction->execute($attributes, $values);
})->throws(ValidationException::class);

test('update or create user with missing name', function (): void {
    $updateOrCreateUserAction = app(UpdateOrCreateUser::class);
    $attributes = ['email' => fake()->email()];
    $values = [];
    
    $updateOrCreateUserAction->execute($attributes, $values);
})->throws(ValidationException::class);

test('update or create user transforms email to lowercase', function (): void {
    $updateOrCreateUserAction = app(UpdateOrCreateUser::class);
    $attributes = ['email' => 'UPPERCASE@EXAMPLE.COM'];
    $values = ['name' => 'Test', 'password' => 'password123'];
    
    $result = $updateOrCreateUserAction->execute($attributes, $values);
    
    expect($result->email)->toBe('uppercase@example.com')
        ->and(User::query()->where('email', 'uppercase@example.com')->exists())->toBeTrue();
});

test('update or create user transforms name to ucfirst', function (): void {
    $updateOrCreateUserAction = app(UpdateOrCreateUser::class);
    $attributes = ['email' => fake()->email()];
    $values = ['name' => 'lowercase name', 'password' => 'password123'];
    
    $result = $updateOrCreateUserAction->execute($attributes, $values);
    
    expect($result->name)->toBe('Lowercase name');
});

test('update or create user with email in values', function (): void {
    $updateOrCreateUserAction = app(UpdateOrCreateUser::class);
    $attributes = ['email' => 'test@example.com'];
    $values = ['email' => 'UPDATED@EXAMPLE.COM', 'name' => 'Test User', 'password' => 'password123'];
    
    $result = $updateOrCreateUserAction->execute($attributes, $values);
    
    expect($result->email)->toBe('updated@example.com')
        ->and($result->name)->toBe('Test user')
        ->and(User::query()->where('email', 'updated@example.com')->where('name', 'Test user')->exists())->toBeTrue();
});

test('update or create user with only attributes', function (): void {
    $updateOrCreateUserAction = app(UpdateOrCreateUser::class);
    $attributes = [
        'email' => 'test@example.com',
        'name' => 'test user',
        'password' => 'password123',
    ];
    $values = [];
    
    $result = $updateOrCreateUserAction->execute($attributes, $values);
    
    expect($result->email)->toBe('test@example.com')
        ->and($result->name)->toBe('test user');
});
