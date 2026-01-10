<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository;

use BadMethodCallException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

use function class_basename;
use function method_exists;

/**
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
     * @param array<int|string, mixed> $arguments
     */
    public function clearCache(string $methodName, array $arguments = []): bool
    {
        return $this->getCacheRepository()->forget(
            $this->generateCacheKey($methodName, $arguments),
        );
    }

    public function clearAllCache(): void
    {
        if ($this->driver !== null) {
            Cache::store($this->driver)->getStore()->flush();

            return;
        }

        Cache::flush();
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    private function generateCacheKey(string $methodName, array $arguments): string
    {
        $repositoryName = class_basename($this->repository);
        $hash = md5($methodName . ':' . serialize($arguments));

        return sprintf('%s:%s:%s:%s', $this->getCachePrefix(), $repositoryName, $methodName, $hash);
    }

    private function getCacheRepository(): CacheRepository
    {
        return $this->driver !== null
            ? Cache::store($this->driver)
            : Cache::store();
    }

    private function getCacheTtl(): int
    {
        $ttl = config('arch.repository_cache.ttl', 3_600);

        return is_int($ttl) ? $ttl : 3_600;
    }

    private function getCachePrefix(): string
    {
        $prefix = config('arch.repository_cache.prefix', 'arch_repo');
        assert(is_string($prefix));

        return $prefix;
    }

    private function isCachingEnabled(): bool
    {
        return (bool) config('arch.repository_cache.enabled', true);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!method_exists($this->repository, $name)) {
            throw new BadMethodCallException(
                sprintf('Method %s does not exist on %s', $name, $this->repository::class),
            );
        }

        if (!$this->isCachingEnabled()) {
            return $this->repository->{$name}(...$arguments);
        }

        return $this->getCacheRepository()->remember(
            $this->generateCacheKey($name, $arguments),
            $this->getCacheTtl(),
            fn () => $this->repository->{$name}(...$arguments),
        );
    }

}
