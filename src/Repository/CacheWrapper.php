<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository;

use BadMethodCallException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

use function class_basename;
use function config;
use function md5;
use function method_exists;
use function serialize;

/**
 * Cache wrapper that provides automatic caching for repository methods.
 * Acts as a proxy to the original repository with caching functionality.
 *
 * Usage:
 * $result = $repository->cache()->paginateByParams(['active' => true]);
 * $result = $repository->cache('my_driver')->paginateByParams(['active' => true]);
 *
 * @method mixed paginateByParams(array<string, mixed> $params, array<string> $withRelations = [], ?int $itemsPerPage = null, array<string> $orderBy = [], array<string> $groupBy = [])
 * @method mixed getOneByParams(\Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params, array<string> $with = [], array<string> $orderBy = [])
 * @method mixed findOneByParams(\Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params, array<string> $with = [], array<string> $orderBy = [])
 * @method mixed countByParams(\Illuminate\Support\Collection<int, mixed>|array<int, array<int, mixed>> $params, array<string> $groupBy = [])
 */
final readonly class CacheWrapper
{

    public function __construct(private object $repository, private ?string $driver = null)
    {
    }

    /**
     * Clear cache for specific method and parameters.
     *
     * @param array<int|string, mixed> $arguments
     */
    public function clearCache(string $methodName, array $arguments = []): bool
    {
        $cacheKey = $this->generateCacheKey($methodName, $arguments);

        return $this->getCacheRepository()->forget($cacheKey);
    }

    /**
     * Clear all cache entries (use with caution).
     */
    public function clearAllCache(): void
    {
        if ($this->driver !== null) {
            Cache::store($this->driver)->getStore()->flush();

            return;
        }

        Cache::flush();
    }

    /**
     * Generate cache key from method name and parameters.
     *
     * @param array<int|string, mixed> $arguments
     */
    private function generateCacheKey(string $methodName, array $arguments): string
    {
        $repositoryName = class_basename($this->repository);
        $serializedArgs = serialize($arguments);
        $hash = md5($methodName . ':' . $serializedArgs);

        return $this->getCachePrefix() . ':' . $repositoryName . ':' . $methodName . ':' . $hash;
    }

    /**
     * Get the configured cache repository instance.
     */
    private function getCacheRepository(): CacheRepository
    {
        if ($this->driver !== null) {
            return Cache::store($this->driver);
        }

        return Cache::store();
    }

    /**
     * Get the configured cache TTL.
     */
    private function getCacheTtl(): int
    {
        $ttl = config('arch.repository_cache.ttl', 3_600);

        return is_int($ttl) ? $ttl : 3_600;
    }

    /**
     * Get the configured cache prefix.
     */
    private function getCachePrefix(): string
    {
        $prefix = config('arch.repository_cache.prefix', 'arch_repo');
        assert(is_string($prefix));

        return $prefix;
    }

    /**
     * Check if repository caching is enabled.
     */
    private function isCachingEnabled(): bool
    {
        return (bool) config('arch.repository_cache.enabled', true);
    }

    /**
     * Magic method to intercept method calls and add caching.
     *
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!method_exists($this->repository, $name)) {
            throw new BadMethodCallException(sprintf('Method %s does not exist on ', $name) . get_class($this->repository));
        }

        if (!$this->isCachingEnabled()) {
            return $this->repository->{$name}(...$arguments);
        }

        $cacheKey = $this->generateCacheKey($name, $arguments);

        return $this->getCacheRepository()->remember(
            $cacheKey,
            $this->getCacheTtl(),
            fn () => $this->repository->{$name}(...$arguments),
        );
    }

}
