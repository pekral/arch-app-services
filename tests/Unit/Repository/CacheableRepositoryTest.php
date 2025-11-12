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

test('cache returns wrapper instance', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $wrapper = $testCacheableUserRepository->cache();

    expect($wrapper)->toBeInstanceOf(CacheWrapper::class);
});

test('cache wrapper calls paginate by params with cache', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    User::factory()->count(5)->create();
    $params = ['name' => 'John'];
    $expectedResult = $testCacheableUserRepository->paginateByParams($params);

    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:paginateByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($expectedResult);

    $result = $testCacheableUserRepository->cache()->paginateByParams($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe($expectedResult->total());
});

test('cache wrapper calls get one by params with cache', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $user = User::factory()->create(['email' => 'test@example.com']);
    $params = ['email' => 'test@example.com'];

    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:getOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($user);

    $result = $testCacheableUserRepository->cache()->getOneByParams($params);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($user->id);
});

test('cache wrapper calls find one by params with cache', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $user = User::factory()->create(['email' => 'test@example.com']);
    $params = ['email' => 'test@example.com'];

    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:findOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($user);

    $result = $testCacheableUserRepository->cache()->findOneByParams($params);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($user->id);
});

test('cache wrapper calls count by params with cache', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    User::factory()->count(3)->create();
    $params = ['name' => 'John'];
    $expectedCount = 3;

    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:countByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($expectedCount);

    $result = $testCacheableUserRepository->cache()->countByParams($params);

    expect($result)->toBe($expectedCount);
});

test('cache wrapper skips cache when disabled', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', false);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    User::factory()->count(3)->create();
    $params = ['name' => 'John'];

    $cacheMock->shouldNotReceive('remember');

    $result = $testCacheableUserRepository->cache()->paginateByParams($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

test('cache wrapper throws exception for non existent method', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    /** @phpstan-ignore-next-line */
    $testCacheableUserRepository->cache()->nonExistentMethod();
})->throws(BadMethodCallException::class, 'Method nonExistentMethod does not exist on ' . TestCacheableUserRepository::class);

test('cache wrapper clear cache', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $methodName = 'testMethod';
    $arguments = ['param1' => 'value1'];

    $cacheMock->shouldReceive('forget')
        ->once()
        ->with(Mockery::pattern(sprintf('/^arch_repo:TestCacheableUserRepository:%s:[a-f0-9]{32}$/', $methodName)))
        ->andReturn(true);

    $result = $testCacheableUserRepository->cache()->clearCache($methodName, $arguments);

    expect($result)->toBeTrue();
});

test('cache wrapper clear all cache', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    Cache::shouldReceive('flush')
        ->once();

    $testCacheableUserRepository->cache()->clearAllCache();

    expect(true)->toBeTrue();
});

test('cache wrapper uses custom configuration', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();

    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 7_200);
    Config::set('arch.repository_cache.prefix', 'custom_prefix');
    User::factory()->create(['email' => 'test@example.com']);
    $params = ['email' => 'test@example.com'];

    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^custom_prefix:TestCacheableUserRepository:getOneByParams:[a-f0-9]{32}$/'),
            7_200,
            Mockery::type('callable'),
        )
        ->andReturn(User::factory()->create());

    $testCacheableUserRepository->cache()->getOneByParams($params);

    expect(true)->toBeTrue();
});

test('cache wrapper uses custom driver', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();

    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

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

    $result = $testCacheableUserRepository->cache($customDriver)->getOneByParams($params);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($expectedUser->id);
});

test('cache wrapper clear cache with custom driver', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

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

    $result = $testCacheableUserRepository->cache($customDriver)->clearCache($methodName, $arguments);

    expect($result)->toBeTrue();
});

test('cache wrapper clear all cache with custom driver', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    $testCacheableUserRepository = new TestCacheableUserRepository();
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

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

    $testCacheableUserRepository->cache($customDriver)->clearAllCache();

    expect(true)->toBeTrue();
});

test('cache wrapper uses default driver when no driver specified', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    $testCacheableUserRepository = new TestCacheableUserRepository();

    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    User::factory()->create(['email' => 'test@example.com']);
    $params = ['email' => 'test@example.com'];
    $expectedUser = User::factory()->create();

    Cache::shouldReceive('store')
        ->once()
        ->withNoArgs()
        ->andReturn($cacheMock);

    $cacheMock->shouldReceive('remember')
        ->once()
        ->with(
            Mockery::pattern('/^arch_repo:TestCacheableUserRepository:getOneByParams:[a-f0-9]{32}$/'),
            3_600,
            Mockery::type('callable'),
        )
        ->andReturn($expectedUser);

    $result = $testCacheableUserRepository->cache()->getOneByParams($params);

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
