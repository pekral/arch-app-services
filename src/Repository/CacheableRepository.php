<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository;

/**
 * Trait for adding caching functionality to repositories.
 * Provides a simple cache wrapper through the cache() method.
 *
 * Usage:
 * $result = $repository->cache()->paginateByParams(['active' => true]);
 * $user = $repository->cache()->getOneByParams(['email' => 'test@example.com']);
 * $count = $repository->cache()->countByParams(['status' => 'active']);
 *
 * Using custom cache driver:
 * $result = $repository->cache('my_driver')->paginateByParams(['active' => true]);
 *
 * Clear cache:
 * $repository->cache()->clearCache('paginateByParams', $arguments);
 * $repository->cache()->clearAllCache();
 */
trait CacheableRepository
{

    /**
     * Get a cache wrapper for this repository.
     * All method calls on the returned object will be automatically cached.
     *
     * @param string|null $driver Optional cache driver name from Laravel's cache.php config
     */
    public function cache(?string $driver = null): CacheWrapper
    {
        return new CacheWrapper($this, $driver);
    }

}
