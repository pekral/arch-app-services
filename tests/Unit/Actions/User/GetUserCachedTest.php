<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Pekral\Arch\Examples\Actions\User\GetUserCached;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function fake;

final class GetUserCachedTest extends TestCase
{

    private GetUserCached $getUserCached;

    /**
     * @var \Mockery\MockInterface&\Illuminate\Contracts\Cache\Repository
     */
    private MockInterface $cacheMock;

    public function testGetUserUsesCache(): void
    {
        // Arrange
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

        // Act
        $result = $this->getUserCached->handle($filters);

        // Assert
        $this->assertEquals($user->id, $result->id);
    }

    public function testGetUserSkipsCacheWhenDisabled(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        $user = User::factory()->create();
        $filters = ['name' => $user->name, 'email' => $user->email];

        $this->cacheMock->shouldNotReceive('remember');

        // Act
        $result = $this->getUserCached->handle($filters);

        // Assert
        $this->assertEquals($user->toArray(), $result->toArray());
    }

    public function testGetUserWithRealDatabase(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        $user = User::factory()->create();
        
        // Act
        $foundUser = $this->getUserCached->handle(['name' => $user->name, 'email' => $user->email]);
        
        // Assert
        $this->assertEquals($user->toArray(), $foundUser->toArray());
    }

    public function testGetNonExistingUserThrowsException(): void
    {
        // Arrange
        Config::set('arch.repository_cache.enabled', false);
        User::factory()->create();
        
        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->getUserCached->handle(['name' => fake()->name(), 'email' => fake()->email()]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheMock = Mockery::mock(CacheRepository::class);
        Cache::shouldReceive('store')->andReturn($this->cacheMock);

        $this->getUserCached = app(GetUserCached::class);

        // Set default cache config
        Config::set('arch.repository_cache.enabled', true);
        Config::set('arch.repository_cache.ttl', 3_600);
        Config::set('arch.repository_cache.prefix', 'arch_repo');
    }

}
