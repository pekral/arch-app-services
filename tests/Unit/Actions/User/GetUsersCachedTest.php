<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Pekral\Arch\Examples\Actions\User\GetUsersCached;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function config;
use function in_array;

final class GetUsersCachedTest extends TestCase
{

    private GetUsersCached $getUsersCached;

    /**
     * @var \Mockery\MockInterface&\Illuminate\Contracts\Cache\Repository
     */
    private MockInterface $cacheMock;

    public function testGetUsersUsesCache(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', true);
        User::factory()->count(30)->create();

        $expectedResult = new LengthAwarePaginator(
            collect(),
            30,
            config()->integer('arch.default_items_per_page'),
            1,
        );

        $this->cacheMock->shouldReceive('remember')
            ->once()
            ->with(
                Mockery::pattern('/^arch_repo:UserRepository:paginateByParams:[a-f0-9]{32}$/'),
                3_600,
                Mockery::type('callable'),
            )
            ->andReturn($expectedResult);

        // Act
        $result = $this->getUsersCached->handle();

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals($expectedResult->total(), $result->total());
    }

    public function testGetUsersSkipsCacheWhenDisabled(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        User::factory()->count(20)->create();

        $this->cacheMock->shouldNotReceive('remember');

        // Act
        $result = $this->getUsersCached->handle();

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(config()->integer('arch.default_items_per_page'), $result);
    }

    public function testGetUsersWithRealDatabase(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        $users = User::factory()->count(30)->create();
        $usersIds = $users->pluck('id')->toArray();

        // Act
        $foundUsers = $this->getUsersCached->handle();

        // Assert
        $this->assertCount(config()->integer('arch.default_items_per_page'), $foundUsers);
        $foundUsers->collect()->each(callback: function (mixed $user) use ($usersIds): void {
            /** @var \Pekral\Arch\Tests\Models\User $user */
            $this->assertTrue(in_array($user->id, $usersIds, true));
        });
    }

    public function testGetUsersWithFiltersUsesCache(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', true);
        User::factory()->count(5)->create(['name' => 'John']);
        User::factory()->count(5)->create(['name' => 'Jane']);
        $filters = ['name' => 'John'];

        $expectedResult = new LengthAwarePaginator(
            collect(),
            5,
            config()->integer('arch.default_items_per_page'),
            1,
        );

        $this->cacheMock->shouldReceive('remember')
            ->once()
            ->with(
                Mockery::pattern('/^arch_repo:UserRepository:paginateByParams:[a-f0-9]{32}$/'),
                3_600,
                Mockery::type('callable'),
            )
            ->andReturn($expectedResult);

        // Act
        $result = $this->getUsersCached->handle($filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals($expectedResult->total(), $result->total());
    }

    public function testGetUsersWithFiltersRealDatabase(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        User::factory()->count(5)->create(['name' => 'John']);
        User::factory()->count(5)->create(['name' => 'Jane']);

        // Act
        $foundUsers = $this->getUsersCached->handle(['name' => 'John']);

        // Assert
        $this->assertCount(5, $foundUsers);
        $foundUsers->collect()->each(callback: function (mixed $user): void {
            /** @var \Pekral\Arch\Tests\Models\User $user */
            $this->assertEquals('John', $user->name);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheMock = Mockery::mock(CacheRepository::class);
        Cache::shouldReceive('store')->andReturn($this->cacheMock);

        $this->getUsersCached = app(GetUsersCached::class);

        // Set default cache config
        Config::set('arch.repository_cache.enabled', true);
        Config::set('arch.repository_cache.ttl', 3_600);
        Config::set('arch.repository_cache.prefix', 'arch_repo');
    }

}
