<?php

declare(strict_types = 1);

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Examples\Actions\User\GetOrCreateUser;
use Pekral\Arch\Tests\Models\User;

test('get or create user creates new record', function (): void {
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = ['email' => 'newuser@example.com'];
    $values = ['name' => 'New User', 'password' => 'password123'];
    
    $result = $getOrCreateUserAction->execute($attributes, $values);
    
    expect($result)->toBeInstanceOf(User::class)
        ->and($result->email)->toBe('newuser@example.com')
        ->and($result->name)->toBe('New user')
        ->and(User::query()->where('email', 'newuser@example.com')->where('name', 'New user')->exists())->toBeTrue();
});

test('get or create user returns existing record', function (): void {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Original Name',
    ]);
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = ['email' => 'existing@example.com'];
    $values = ['name' => 'Updated Name'];
    
    $result = $getOrCreateUserAction->execute($attributes, $values);
    
    expect($result->id)->toBe($existingUser->id)
        ->and($result->email)->toBe('existing@example.com')
        ->and($result->name)->toBe('Original Name');
    
    $existingUser->refresh();
    expect($existingUser->name)->toBe('Original Name');
});

test('get or create user with invalid email', function (): void {
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = ['email' => 'invalid-email'];
    $values = ['name' => 'Test Name'];
    
    $getOrCreateUserAction->execute($attributes, $values);
})->throws(ValidationException::class);

test('get or create user with missing email', function (): void {
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = [];
    $values = ['name' => 'Test Name'];
    
    $getOrCreateUserAction->execute($attributes, $values);
})->throws(ValidationException::class);

test('get or create user with missing name', function (): void {
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = ['email' => fake()->email()];
    $values = [];
    
    $getOrCreateUserAction->execute($attributes, $values);
})->throws(ValidationException::class);

test('get or create user transforms email to lowercase', function (): void {
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = ['email' => 'UPPERCASE@EXAMPLE.COM'];
    $values = ['name' => 'Test', 'password' => 'password123'];
    
    $result = $getOrCreateUserAction->execute($attributes, $values);
    
    expect($result->email)->toBe('uppercase@example.com')
        ->and(User::query()->where('email', 'uppercase@example.com')->exists())->toBeTrue();
});

test('get or create user transforms name to ucfirst', function (): void {
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = ['email' => fake()->email()];
    $values = ['name' => 'lowercase name', 'password' => 'password123'];
    
    $result = $getOrCreateUserAction->execute($attributes, $values);
    
    expect($result->name)->toBe('Lowercase name');
});

test('get or create user with email in values', function (): void {
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = ['email' => 'test@example.com'];
    $values = ['email' => 'UPDATED@EXAMPLE.COM', 'name' => 'Test User', 'password' => 'password123'];
    
    $result = $getOrCreateUserAction->execute($attributes, $values);
    
    expect($result->email)->toBe('updated@example.com')
        ->and($result->name)->toBe('Test user')
        ->and(User::query()->where('email', 'updated@example.com')->where('name', 'Test user')->exists())->toBeTrue();
});

test('get or create user with only attributes', function (): void {
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = [
        'email' => 'test@example.com',
        'name' => 'test user',
        'password' => 'password123',
    ];
    $values = [];
    
    $result = $getOrCreateUserAction->execute($attributes, $values);
    
    expect($result->email)->toBe('test@example.com')
        ->and($result->name)->toBe('test user');
});

test('get or create user returns existing with empty values', function (): void {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Original Name',
    ]);
    $getOrCreateUserAction = app(GetOrCreateUser::class);
    $attributes = [
        'email' => 'existing@example.com',
        'name' => 'Original Name',
    ];
    $values = [];
    
    $result = $getOrCreateUserAction->execute($attributes, $values);
    
    expect($result->id)->toBe($existingUser->id);
    
    $existingUser->refresh();
    expect($existingUser->name)->toBe('Original Name')
        ->and($result->name)->toBe('Original Name');
});
