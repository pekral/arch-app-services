<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository;

/**
 * Interface for repositories that support caching functionality.
 *
 * Provides a contract for cache wrapper access.
 */
interface Cacheable
{

    /**
     * Get a cache wrapper for this repository.
     * All method calls on the returned object will be automatically cached.
     *
     * @param string|null $driver Optional cache driver name from Laravel's cache.php config
     * @return \Pekral\Arch\Repository\CacheWrapper Cache wrapper instance
     */
    public function cache(?string $driver = null): CacheWrapper;

}
