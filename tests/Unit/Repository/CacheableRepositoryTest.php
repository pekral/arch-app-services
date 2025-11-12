<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Repository;

use BadMethodCallException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Pekral\Arch\Repository\CacheableRepository;
use Pekral\Arch\Repository\CacheWrapper;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($this->cacheMock);

    $this->testCacheableUserRepository = new TestCacheableUserRepository();

    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
});

test('cache returns wrapper instance', function (): void {
    $wrapper = $this->testCacheableUserRepository->cache();

    expect($wrapper)->toBeInstanceOf(CacheWrapper::class);
});

test('cache wrapper calls paginate by params with cache', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    User::factory()->count(5)->create();
    $params = ['name' => 'John'];
    $expectedResult = $this->testCacheableUserRepository->paginateByParams($params);

    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:paginateByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($expectedResult);

    $result = $this->testCacheableUserRepository->cache()->paginateByParams($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe($expectedResult->total());
});

test('cache wrapper calls get one by params with cache', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    $user = User::factory()->create(['email' => 'test@example.com']);
    $params = ['email' => 'test@example.com'];

    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:getOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($user);

    $result = $this->testCacheableUserRepository->cache()->getOneByParams($params);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($user->id);
});

test('cache wrapper calls find one by params with cache', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    $user = User::factory()->create(['email' => 'test@example.com']);
    $params = ['email' => 'test@example.com'];

    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:findOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($user);

    $result = $this->testCacheableUserRepository->cache()->findOneByParams($params);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($user->id);
});

test('cache wrapper calls count by params with cache', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    User::factory()->count(3)->create();
    $params = ['name' => 'John'];
    $expectedCount = 3;

    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:countByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($expectedCount);

    $result = $this->testCacheableUserRepository->cache()->countByParams($params);

    expect($result)->toBe($expectedCount);
});

test('cache wrapper skips cache when disabled', function (): void {
    Config::set('arch.repository_cache.enabled', false);
    User::factory()->count(3)->create();
    $params = ['name' => 'John'];

    $this->cacheMock->shouldNotReceive('remember');

    $result = $this->testCacheableUserRepository->cache()->paginateByParams($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

test('cache wrapper throws exception for non existent method', function (): void {
    /** @phpstan-ignore-next-line */
    $this->testCacheableUserRepository->cache()->nonExistentMethod();
})->throws(BadMethodCallException::class, 'Method nonExistentMethod does not exist on ' . TestCacheableUserRepository::class);

test('cache wrapper clear cache', function (): void {
    $methodName = 'testMethod';
    $arguments = ['param1' => 'value1'];

    $this->cacheMock->shouldReceive('forget')
        ->once()
        ->with(Mockery::pattern(sprintf('/^arch_repo:TestCacheableUserRepository:%s:[a-f0-9]{32}$/', $methodName)))
        ->andReturn(true);

    $result = $this->testCacheableUserRepository->cache()->clearCache($methodName, $arguments);

    expect($result)->toBeTrue();
});

test('cache wrapper clear all cache', function (): void {
    Cache::shouldReceive('flush')
        ->once();

    $this->testCacheableUserRepository->cache()->clearAllCache();

    expect(true)->toBeTrue();
});

test('cache wrapper uses custom configuration', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 7_200);
    Config::set('arch.repository_cache.prefix', 'custom_prefix');
    User::factory()->create(['email' => 'test@example.com']);
    $params = ['email' => 'test@example.com'];

    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^custom_prefix:TestCacheableUserRepository:getOneByParams:[a-f0-9]{32}$/'),
            7_200,
            Mockery::type('callable'),
        )
        ->andReturn(User::factory()->create());

    $this->testCacheableUserRepository->cache()->getOneByParams($params);

    expect(true)->toBeTrue();
});

test('cache wrapper uses custom driver', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    $customDriver = 'my_driver';
    $customCacheMock = Mockery::mock(CacheRepository::class);
    User::factory()->create(['email' => 'test@example.com']);
    $params = ['email' => 'test@example.com'];
    $expectedUser = User::factory()->create();

    Cache::shouldReceive('store')
        ->once()
        ->with($customDriver)
        ->andReturn($customCacheMock);

    $customCacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:getOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($expectedUser);

    $result = $this->testCacheableUserRepository->cache($customDriver)->getOneByParams($params);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($expectedUser->id);
});

test('cache wrapper clear cache with custom driver', function (): void {
    $customDriver = 'my_driver';
    $customCacheMock = Mockery::mock(CacheRepository::class);
    $methodName = 'testMethod';
    $arguments = ['param1' => 'value1'];

    Cache::shouldReceive('store')
        ->once()
        ->with($customDriver)
        ->andReturn($customCacheMock);

    $customCacheMock->shouldReceive('forget')
        ->once()
        ->with(Mockery::pattern(sprintf('/^arch_repo:TestCacheableUserRepository:%s:[a-f0-9]{32}$/', $methodName)))
        ->andReturn(true);

    $result = $this->testCacheableUserRepository->cache($customDriver)->clearCache($methodName, $arguments);

    expect($result)->toBeTrue();
});

test('cache wrapper clear all cache with custom driver', function (): void {
    $customDriver = 'my_driver';
    $customCacheMock = Mockery::mock(CacheRepository::class);
    $storeMock = Mockery::mock();

    Cache::shouldReceive('store')
        ->once()
        ->with($customDriver)
        ->andReturn($customCacheMock);

    $customCacheMock->shouldReceive('getStore')
        ->once()
        ->andReturn($storeMock);

    $storeMock->shouldReceive('flush')
        ->once();

    $this->testCacheableUserRepository->cache($customDriver)->clearAllCache();

    expect(true)->toBeTrue();
});

test('cache wrapper uses default driver when no driver specified', function (): void {
    Config::set('arch.repository_cache.enabled', true);
    User::factory()->create(['email' => 'test@example.com']);
    $params = ['email' => 'test@example.com'];
    $expectedUser = User::factory()->create();

    Cache::shouldReceive('store')
        ->once()
        ->withNoArgs()
        ->andReturn($this->cacheMock);

    $this->cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:getOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($expectedUser);

    $result = $this->testCacheableUserRepository->cache()->getOneByParams($params);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($expectedUser->id);
});

/**
 * @extends \Pekral\Arch\Repository\Mysql\BaseRepository<\Pekral\Arch\Tests\Models\User>
 */
final class TestCacheableUserRepository extends BaseRepository
{

    use CacheableRepository;

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
