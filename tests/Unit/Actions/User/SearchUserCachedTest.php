<?php

declare(strict_types = 1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Pekral\Arch\Examples\Actions\User\SearchUserCached;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($this->cacheMock);

    $this->searchUserCached = app(SearchUserCached::class);

    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
});

test('search user uses cache', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    $user = User::factory()->create();
    $filters = ['name' => $user->name, 'email' => $user->email];
    
    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:UserRepository:findOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($user);

    $result = $this->searchUserCached->handle($filters);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($user->id);
});

test('search user skips cache when disabled', function (): void {
    Config::set('arch.repository_cache.enabled', false);
    $user = User::factory()->create();
    $filters = ['name' => $user->name, 'email' => $user->email];

    $this->cacheMock->shouldNotReceive('remember');

    $result = $this->searchUserCached->handle($filters);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($user->id)
        ->and($result->name)->toBe($user->name)
        ->and($result->email)->toBe($user->email);
});

test('search user with real database', function (): void {
    Config::set('arch.repository_cache.enabled', false);
    $user = User::factory()->create();
    
    $foundUser = $this->searchUserCached->handle(['name' => $user->name, 'email' => $user->email]);
    
    expect($foundUser)->not->toBeNull()
        ->and($foundUser->id)->toBe($user->id)
        ->and($foundUser->name)->toBe($user->name)
        ->and($foundUser->email)->toBe($user->email);
});

test('search non existing user returns null', function (): void {
    Config::set('arch.repository_cache.enabled', false);
    User::factory()->create();
    
    $foundUser = $this->searchUserCached->handle(['name' => fake()->name(), 'email' => fake()->email()]);
    
    expect($foundUser)->toBeNull();
});

test('search non existing user caches null', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    User::factory()->create();
    $filters = ['name' => fake()->name(), 'email' => fake()->email()];
    
    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:UserRepository:findOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn(null);

    $result = $this->searchUserCached->handle($filters);
    
    expect($result)->toBeNull();
});
