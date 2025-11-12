<?php

declare(strict_types = 1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Pekral\Arch\Examples\Actions\User\GetUsersCached;
use Pekral\Arch\Tests\Models\User;

test('get users uses cache', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $getUsersCached = app(GetUsersCached::class);
    
    User::factory()->count(30)->create();

    $expectedResult = new LengthAwarePaginator(
        collect(),
        30,
        config()->integer('arch.default_items_per_page'),
        1,
    );

    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:UserRepository:paginateByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($expectedResult);

    $result = $getUsersCached->handle();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe($expectedResult->total());
});

test('get users skips cache when disabled', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', false);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $getUsersCached = app(GetUsersCached::class);
    
    User::factory()->count(20)->create();

    $cacheMock->shouldNotReceive('remember');

    $result = $getUsersCached->handle();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(config()->integer('arch.default_items_per_page'));
});

test('get users with real database', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', false);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $getUsersCached = app(GetUsersCached::class);
    
    $users = User::factory()->count(30)->create();
    $usersIds = $users->pluck('id')->toArray();

    $foundUsers = $getUsersCached->handle();

    expect($foundUsers)->toHaveCount(config()->integer('arch.default_items_per_page'));
    
    $foundUsers->collect()->each(function (User $user) use ($usersIds): void {
        expect(in_array($user->id, $usersIds, true))->toBeTrue();
    });
});

test('get users with filters uses cache', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $getUsersCached = app(GetUsersCached::class);
    
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(5)->create(['name' => 'Jane']);
    $filters = ['name' => 'John'];

    $expectedResult = new LengthAwarePaginator(
        collect(),
        5,
        config()->integer('arch.default_items_per_page'),
        1,
    );

    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:UserRepository:paginateByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($expectedResult);

    $result = $getUsersCached->handle($filters);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe($expectedResult->total());
});

test('get users with filters real database', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', false);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $getUsersCached = app(GetUsersCached::class);
    
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(5)->create(['name' => 'Jane']);

    $foundUsers = $getUsersCached->handle(['name' => 'John']);

    expect($foundUsers)->toHaveCount(5);
    
    $foundUsers->collect()->each(function (User $user): void {
        expect($user->name)->toBe('John');
    });
});
