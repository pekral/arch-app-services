<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Repository;

use Illuminate\Database\Eloquent\Builder;
use Pekral\Arch\Examples\Services\User\UserRepository;
use Pekral\Arch\Tests\Models\User;

test('custom where methods work on query builder', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    
    $query = $userRepository->createQueryBuilder()->whereName('John Doe');
    
    expect($query)->toBeInstanceOf(Builder::class)
        ->and($query->count())->toBe(1)
        ->and($query->first())->toBeInstanceOf(User::class);
    
    $user = $query->first();
    expect($user)->not->toBeNull();
    assert($user !== null);
    expect($user->name)->toBe('John Doe');
});

test('custom where methods can be chained', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    User::factory()->create(['name' => 'John Smith', 'email' => 'johnsmith@example.com']);
    
    $query = $userRepository->createQueryBuilder()
        ->whereName('John Doe')
        ->whereEmail('john@example.com');
    
    expect($query)->toBeInstanceOf(Builder::class)
        ->and($query->count())->toBe(1);
    
    $user = $query->first();
    expect($user)->not->toBeNull();
    assert($user !== null);
    expect($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com');
});

test('custom where methods work with exists', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    
    $exists = $userRepository->createQueryBuilder()
        ->whereName('John Doe')
        ->whereEmail('john@example.com')
        ->exists();
    
    expect($exists)->toBeTrue();
    
    $notExists = $userRepository->createQueryBuilder()
        ->whereName('Non Existent')
        ->exists();
    
    expect($notExists)->toBeFalse();
});

test('custom where methods work with query method', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    
    $query = $userRepository->query()->whereName('John Doe');
    
    expect($query)->toBeInstanceOf(Builder::class)
        ->and($query->count())->toBe(1);
    
    $user = $query->first();
    expect($user)->not->toBeNull();
    assert($user !== null);
    expect($user->name)->toBe('John Doe');
});

test('custom where methods return correct builder type', function (): void {
    $userRepository = app(UserRepository::class);
    
    $builder = $userRepository->createQueryBuilder();
    
    expect($builder)->toBeInstanceOf(Builder::class);
    
    $builderAfterWhere = $builder->whereName('Test');
    
    expect($builderAfterWhere)->toBeInstanceOf(Builder::class)
        ->and($builderAfterWhere)->toBe($builder);
});
