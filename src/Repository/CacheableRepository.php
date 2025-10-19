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
 * Clear cache:
 * $repository->cache()->clearCache('paginateByParams', $arguments);
 * $repository->cache()->clearAllCache();
 */
trait CacheableRepository
{

    /**
     * Get a cache wrapper for this repository.
     * All method calls on the returned object will be automatically cached.
     */
    public function cache(): CacheWrapper
    {
        return new CacheWrapper($this);
    }

}
