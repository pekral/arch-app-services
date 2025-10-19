<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Pekral\Arch\Examples\Actions\User\SearchUserCached;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function fake;

final class SearchUserCachedTest extends TestCase
{

    private SearchUserCached $searchUserCached;

    /**
     * @var \Mockery\MockInterface&\Illuminate\Contracts\Cache\Repository
     */
    private MockInterface $cacheMock;

    public function testSearchUserUsesCache(): void
    {
        // Arrange
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

        // Act
        $result = $this->searchUserCached->handle($filters);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function testSearchUserSkipsCacheWhenDisabled(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        $user = User::factory()->create();
        $filters = ['name' => $user->name, 'email' => $user->email];

        $this->cacheMock->shouldNotReceive('remember');

        // Act
        $result = $this->searchUserCached->handle($filters);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($user->toArray(), $result->toArray());
    }

    public function testSearchUserWithRealDatabase(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        $user = User::factory()->create();
        
        // Act
        $foundUser = $this->searchUserCached->handle(['name' => $user->name, 'email' => $user->email]);
        
        // Assert
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->toArray(), $foundUser->toArray());
    }

    public function testSearchNonExistingUserReturnsNull(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        User::factory()->create();
        
        // Act
        $foundUser = $this->searchUserCached->handle(['name' => fake()->name(), 'email' => fake()->email()]);
        
        // Assert
        $this->assertNull($foundUser);
    }

    public function testSearchNonExistingUserCachesNull(): void
    {
        // Arrange
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

        // Act
        $result = $this->searchUserCached->handle($filters);
        
        // Assert
        $this->assertNull($result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheMock = Mockery::mock(CacheRepository::class);
        Cache::shouldReceive('store')->andReturn($this->cacheMock);

        $this->searchUserCached = app(SearchUserCached::class);

        // Set default cache config
        Config::set('arch.repository_cache.enabled', true);
        Config::set('arch.repository_cache.ttl', 3_600);
        Config::set('arch.repository_cache.prefix', 'arch_repo');
    }

}
