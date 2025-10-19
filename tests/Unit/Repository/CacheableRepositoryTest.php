<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Repository;

use BadMethodCallException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Pekral\Arch\Repository\CacheableRepository;
use Pekral\Arch\Repository\CacheWrapper;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class CacheableRepositoryTest extends TestCase
{

    private TestCacheableUserRepository $testCacheableUserRepository;

    /**
     * @var \Mockery\MockInterface&\Illuminate\Contracts\Cache\Repository
     */
    private MockInterface $cacheMock;

    public function testCacheReturnsWrapperInstance(): void
    {
        // Act
        $wrapper = $this->testCacheableUserRepository->cache();

        // Assert
        $this->assertInstanceOf(CacheWrapper::class, $wrapper);
    }

    public function testCacheWrapperCallsPaginateByParamsWithCache(): void
    {
        // Arrange
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

        // Act
        $result = $this->testCacheableUserRepository->cache()->paginateByParams($params);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals($expectedResult->total(), $result->total());
    }

    public function testCacheWrapperCallsGetOneByParamsWithCache(): void
    {
        // Arrange
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

        // Act
        $result = $this->testCacheableUserRepository->cache()->getOneByParams($params);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    public function testCacheWrapperCallsFindOneByParamsWithCache(): void
    {
        // Arrange
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

        // Act
        $result = $this->testCacheableUserRepository->cache()->findOneByParams($params);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    public function testCacheWrapperCallsCountByParamsWithCache(): void
    {
        // Arrange
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

        // Act
        $result = $this->testCacheableUserRepository->cache()->countByParams($params);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    public function testCacheWrapperSkipsCacheWhenDisabled(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        User::factory()->count(3)->create();
        $params = ['name' => 'John'];

        $this->cacheMock->shouldNotReceive('remember');

        // Act
        $result = $this->testCacheableUserRepository->cache()->paginateByParams($params);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function testCacheWrapperThrowsExceptionForNonExistentMethod(): void
    {
        // Arrange & Assert
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method nonExistentMethod does not exist on ' . TestCacheableUserRepository::class);

        // Act
        /** @phpstan-ignore-next-line */
        $this->testCacheableUserRepository->cache()->nonExistentMethod();
    }

    public function testCacheWrapperClearCache(): void
    {
        // Arrange
        $methodName = 'testMethod';
        $arguments = ['param1' => 'value1'];

        $this->cacheMock->shouldReceive('forget')
            ->once()
            ->with(Mockery::pattern(sprintf('/^arch_repo:TestCacheableUserRepository:%s:[a-f0-9]{32}$/', $methodName)))
            ->andReturn(true);

        // Act
        $result = $this->testCacheableUserRepository->cache()->clearCache($methodName, $arguments);

        // Assert
        $this->assertTrue($result);
    }

    public function testCacheWrapperClearAllCache(): void
    {
        // Arrange
        Cache::shouldReceive('flush')
            ->once();

        // Act
        $this->testCacheableUserRepository->cache()->clearAllCache();

        // Assert - expectations verified by Mockery
        $this->addToAssertionCount(1);
    }

    public function testCacheWrapperUsesCustomConfiguration(): void
    {
        // Arrange
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

        // Act
        $this->testCacheableUserRepository->cache()->getOneByParams($params);

        // Assert - expectations verified by Mockery
        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheMock = Mockery::mock(CacheRepository::class);
        Cache::shouldReceive('store')->andReturn($this->cacheMock);

        $this->testCacheableUserRepository = new TestCacheableUserRepository();

        // Set default cache config
        Config::set('arch.repository_cache.enabled', true);
        Config::set('arch.repository_cache.ttl', 3_600);
        Config::set('arch.repository_cache.prefix', 'arch_repo');
    }

}

/**
 * Test implementation of CacheableRepository trait for testing purposes.
 *
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
