<?php

declare(strict_types = 1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Pekral\Arch\Examples\Actions\User\SearchUserCached;
use Pekral\Arch\Tests\Models\User;

test('search user uses cache', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $searchUserCached = app(SearchUserCached::class);
    
    $user = User::factory()->create();
    $filters = ['name' => $user->name, 'email' => $user->email];
    
    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:UserRepository:findOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($user);

    $result = $searchUserCached->handle($filters);

    expect($result)->not->toBeNull();
    
    assert($result instanceof User);
    expect($result->id)->toBe($user->id);
});

test('search user skips cache when disabled', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', false);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $searchUserCached = app(SearchUserCached::class);
    
    $user = User::factory()->create();
    $filters = ['name' => $user->name, 'email' => $user->email];

    $cacheMock->shouldNotReceive('remember');

    $result = $searchUserCached->handle($filters);

    expect($result)->not->toBeNull();
    
    assert($result instanceof User);
    expect($result->id)->toBe($user->id)
        ->and($result->name)->toBe($user->name)
        ->and($result->email)->toBe($user->email);
});

test('search user with real database', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', false);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $searchUserCached = app(SearchUserCached::class);
    
    $user = User::factory()->create();
    
    $foundUser = $searchUserCached->handle(['name' => $user->name, 'email' => $user->email]);
    
    expect($foundUser)->not->toBeNull();
    
    assert($foundUser instanceof User);
    expect($foundUser->id)->toBe($user->id)
        ->and($foundUser->name)->toBe($user->name)
        ->and($foundUser->email)->toBe($user->email);
});

test('search non existing user returns null', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', false);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $searchUserCached = app(SearchUserCached::class);
    
    User::factory()->create();
    
    $foundUser = $searchUserCached->handle(['name' => fake()->name(), 'email' => fake()->email()]);
    
    expect($foundUser)->toBeNull();
});

test('search non existing user caches null', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cacheMock);
    
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    
    $searchUserCached = app(SearchUserCached::class);
    
    User::factory()->create();
    $filters = ['name' => fake()->name(), 'email' => fake()->email()];
    
    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:UserRepository:findOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn(null);

    $result = $searchUserCached->handle($filters);
    
    expect($result)->toBeNull();
});
