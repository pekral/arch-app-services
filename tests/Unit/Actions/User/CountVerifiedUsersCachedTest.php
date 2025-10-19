<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Pekral\Arch\Examples\Actions\User\CountVerifiedUsersCached;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class CountVerifiedUsersCachedTest extends TestCase
{

    private CountVerifiedUsersCached $countVerifiedUsersCached;

    /**
     * @var \Mockery\MockInterface&\Illuminate\Contracts\Cache\Repository
     */
    private MockInterface $cacheMock;

    public function testCountVerifiedUsersUsesCache(): void
    {
        // Arrange
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

        // Act
        $result = $this->countVerifiedUsersCached->handle();

        // Assert
        $this->assertSame(3, $result);
    }

    public function testCountVerifiedUsersSkipsCacheWhenDisabled(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        User::factory()->count(5)->create(['email_verified_at' => null]);
        User::factory()->count(3)->create(['email_verified_at' => now()]);

        $this->cacheMock->shouldNotReceive('remember');

        // Act
        $result = $this->countVerifiedUsersCached->handle();

        // Assert
        $this->assertSame(3, $result);
    }

    public function testCountVerifiedUsersWithRealDatabase(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        User::factory()->count(10)->create(['email_verified_at' => null]);
        $verifiedUsers = User::factory()->count(7)->create(['email_verified_at' => now()]);
        
        // Act
        $result = $this->countVerifiedUsersCached->handle();

        // Assert
        $this->assertEquals($verifiedUsers->count(), $result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheMock = Mockery::mock(CacheRepository::class);
        Cache::shouldReceive('store')->andReturn($this->cacheMock);

        $this->countVerifiedUsersCached = app(CountVerifiedUsersCached::class);

        // Set default cache config
        Config::set('arch.repository_cache.enabled', true);
        Config::set('arch.repository_cache.ttl', 3_600);
        Config::set('arch.repository_cache.prefix', 'arch_repo');
    }

}
