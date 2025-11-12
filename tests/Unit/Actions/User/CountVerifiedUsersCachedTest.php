<?php

declare(strict_types = 1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Pekral\Arch\Examples\Actions\User\CountVerifiedUsersCached;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($this->cacheMock);

    $this->countVerifiedUsersCached = app(CountVerifiedUsersCached::class);

    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
});

test('count verified users uses cache', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    User::factory()->count(5)->create(['email_verified_at' => null]);
    User::factory()->count(3)->create(['email_verified_at' => now()]);
    
    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:UserRepository:countByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn(3);

    $result = $this->countVerifiedUsersCached->handle();

    expect($result)->toBe(3);
});

test('count verified users skips cache when disabled', function (): void {
    Config::set('arch.repository_cache.enabled', false);
    User::factory()->count(5)->create(['email_verified_at' => null]);
    User::factory()->count(3)->create(['email_verified_at' => now()]);

    $this->cacheMock->shouldNotReceive('remember');

    $result = $this->countVerifiedUsersCached->handle();

    expect($result)->toBe(3);
});

test('count verified users with real database', function (): void {
    Config::set('arch.repository_cache.enabled', false);
    User::factory()->count(10)->create(['email_verified_at' => null]);
    $verifiedUsers = User::factory()->count(7)->create(['email_verified_at' => now()]);
    
    $result = $this->countVerifiedUsersCached->handle();

    expect($result)->toBe($verifiedUsers->count());
});
