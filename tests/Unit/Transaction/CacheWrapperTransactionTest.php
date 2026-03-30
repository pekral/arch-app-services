<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Transaction;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use Mockery;
use Pekral\Arch\Repository\CacheableRepository;
use Pekral\Arch\Repository\CacheWrapper;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Tests\Models\User;
use stdClass;

test('clear cache defers invalidation until after commit when inside transaction', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $state = new stdClass();
    $state->cleared = false;

    $cacheMock->shouldReceive('forget')
        ->once()
        ->andReturnUsing(static function () use ($state): bool {
            $state->cleared = true;

            return true;
        });

    $repository = new CacheWrapperTransactionRepository();
    DB::beginTransaction();

    $repository->cache()->clearCache('testMethod', []);

    expect($state->cleared)->toBeFalse();

    DB::commit();

    expect($state->cleared)->toBeTrue();
});

test('clear cache defers invalidation on custom connection until after commit', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $state = new stdClass();
    $state->cleared = false;

    $cacheMock->shouldReceive('forget')
        ->once()
        ->andReturnUsing(static function () use ($state): bool {
            $state->cleared = true;

            return true;
        });

    $repository = new CacheWrapperTransactionRepository();
    DB::connection('testing')->beginTransaction();

    $repository->cache(null, 'testing')->clearCache('testMethod', []);

    expect($state->cleared)->toBeFalse();

    DB::connection('testing')->commit();

    expect($state->cleared)->toBeTrue();
});

test('clear cache executes immediately when outside transaction', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    $dbConnection = Mockery::mock(Connection::class);

    DB::shouldReceive('connection')
        ->once()
        ->with('cache_test')
        ->andReturn($dbConnection);

    $dbConnection->shouldReceive('transactionLevel')->once()->andReturn(0);

    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $cacheMock->shouldReceive('forget')->once()->andReturn(true);

    $repository = new CacheWrapperTransactionRepository();

    $wrapper = new CacheWrapper($repository, null, 'cache_test');

    $result = $wrapper->clearCache('testMethod', []);

    expect($result)->toBeTrue();
});

test('clear all cache defers flush until after commit when inside transaction', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $state = new stdClass();
    $state->flushed = false;

    Cache::shouldReceive('flush')
        ->once()
        ->andReturnUsing(static function () use ($state): void {
            $state->flushed = true;
        });

    $repository = new CacheWrapperTransactionRepository();
    DB::beginTransaction();

    $repository->cache()->clearAllCache();

    expect($state->flushed)->toBeFalse();

    DB::commit();

    expect($state->flushed)->toBeTrue();
});

test('clear all cache flushes immediately when outside transaction', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    $dbConnection = Mockery::mock(Connection::class);

    DB::shouldReceive('connection')
        ->once()
        ->with('cache_test')
        ->andReturn($dbConnection);

    $dbConnection->shouldReceive('transactionLevel')->once()->andReturn(0);

    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    Cache::shouldReceive('flush')->once();

    $repository = new CacheWrapperTransactionRepository();

    $wrapper = new CacheWrapper($repository, null, 'cache_test');

    $wrapper->clearAllCache();

    expect(true)->toBeTrue();
});

test('clear cache does not execute when transaction is rolled back', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $cacheMock->shouldNotReceive('forget');

    $repository = new CacheWrapperTransactionRepository();
    DB::beginTransaction();

    $repository->cache()->clearCache('testMethod', []);

    DB::rollBack();

    expect(true)->toBeTrue();
});

test('clear all cache does not execute when transaction is rolled back', function (): void {
    $cacheMock = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->byDefault()->andReturn($cacheMock);
    Config::set('arch.repository_cache.enabled', true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    Cache::shouldNotReceive('flush');

    $repository = new CacheWrapperTransactionRepository();
    DB::beginTransaction();

    $repository->cache()->clearAllCache();

    DB::rollBack();

    expect(true)->toBeTrue();
});

/**
 * @extends \Pekral\Arch\Repository\Mysql\BaseRepository<\Pekral\Arch\Tests\Models\User>
 */
final class CacheWrapperTransactionRepository extends BaseRepository
{

    use CacheableRepository;

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
