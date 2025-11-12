<?php

declare(strict_types = 1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Pekral\Arch\Examples\Actions\User\GetUserCached;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($this->cacheMock);

    $this->getUserCached = app(GetUserCached::class);

    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
});

test('get user uses cache', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    $user = User::factory()->create();
    $filters = ['name' => $user->name, 'email' => $user->email];
    
    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:UserRepository:getOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($user);

    $result = $this->getUserCached->handle($filters);

    expect($result->id)->toBe($user->id);
});

test('get user skips cache when disabled', function (): void {
    Config::set('arch.repository_cache.enabled', false);
    $user = User::factory()->create();
    $filters = ['name' => $user->name, 'email' => $user->email];

    $this->cacheMock->shouldNotReceive('remember');

    $result = $this->getUserCached->handle($filters);

    expect($result->id)->toBe($user->id)
        ->and($result->name)->toBe($user->name)
        ->and($result->email)->toBe($user->email);
});

test('get user with real database', function (): void {
    Config::set('arch.repository_cache.enabled', false);
    $user = User::factory()->create();
    
    $foundUser = $this->getUserCached->handle(['name' => $user->name, 'email' => $user->email]);
    
    expect($foundUser->id)->toBe($user->id)
        ->and($foundUser->name)->toBe($user->name)
        ->and($foundUser->email)->toBe($user->email);
});

test('get non existing user throws exception', function (): void {
    Config::set('arch.repository_cache.enabled', false);
    User::factory()->create();
    
    $this->getUserCached->handle(['name' => fake()->name(), 'email' => fake()->email()]);
})->throws(ModelNotFoundException::class);
