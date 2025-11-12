<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Repository\Mysql;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Pekral\Arch\Examples\Services\User\UserRepository;
use Pekral\Arch\Tests\Models\User;

test('get one by params with order by', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
    
    $foundUser = $userRepository->getOneByParams(['email' => 'alice@example.com'], [], ['name' => 'desc']);
    
    expect($foundUser)->toBeInstanceOf(User::class)
        ->and($foundUser->email)->toBe('alice@example.com');
});

test('find one by params with order by', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
    
    $foundUser = $userRepository->findOneByParams(['email' => 'alice@example.com'], [], ['name' => 'desc']);
    
    expect($foundUser)->toBeInstanceOf(User::class);
    assert($foundUser !== null);
    expect($foundUser->email)->toBe('alice@example.com');
});

test('count by params with group by', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(3)->create(['name' => 'Jane']);
    
    $count = $userRepository->countByParams([], ['name']);
    
    expect($count)->toBeGreaterThanOrEqual(2);
});

test('paginate by params returns paginated results', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->count(20)->create();

    $result = $userRepository->paginateByParams([]);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(15);
});

test('paginate by params with filters', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(5)->create(['name' => 'Jane']);
    
    $result = $userRepository->paginateByParams(['name' => 'John']);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(5);
});

test('paginate by params with custom per page', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->count(20)->create();
    
    $result = $userRepository->paginateByParams([], [], 10);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(10);
});

test('paginate by params with order by', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);
    
    $result = $userRepository->paginateByParams([], [], null, ['name' => 'desc']);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(2);
    
    $firstUser = $result->first();
    expect($firstUser)->not->toBeNull();
    assert($firstUser !== null);
    expect($firstUser->name)->toBe('Bob');
});

test('paginate by params with group by', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(3)->create(['name' => 'Jane']);
    
    $result = $userRepository->paginateByParams([], [], null, [], ['name']);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(2);
});

test('paginate by params with empty with relations', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->count(5)->create();
    
    $result = $userRepository->paginateByParams([], []);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(5);
});

test('paginate by params with non existent relation throws exception', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->count(5)->create();
    
    $userRepository->paginateByParams([], ['non_existent_relation']);
})->throws(RelationNotFoundException::class, 'non_existent_relation');

test('query returns builder instance', function (): void {
    $userRepository = app(UserRepository::class);
    User::factory()->count(3)->create();
    
    $query = $userRepository->query();
    
    expect($query)->toBeInstanceOf(Builder::class)
        ->and($query->count())->toBe(3);
});
