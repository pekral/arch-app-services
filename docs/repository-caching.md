# Repository Caching

Repositories can opt in to transparent read caching by using the `Pekral\Arch\Repository\CacheableRepository` trait. The trait exposes a `cache()` helper that returns a `CacheWrapper` proxy. All method calls made through this proxy are cached automatically.

## Enabling caching for a repository

```php
final class UserRepository extends BaseRepository
{
    use CacheableRepository;

    protected function getModelClassName(): string
    {
        return User::class;
    }
}
```

Wrap read calls in the proxy to cache results:

```php
$user = $userRepository->cache()->getOneByParams(['email' => 'user@example.com']);

$paginator = $userRepository->cache()->paginateByParams(
    ['active' => true],
    ['posts'],
    15,
    ['created_at' => 'desc'],
);
```

> The proxy simply forwards method calls to the underlying repository and stores the result in Laravel's cache store. Write operations should continue to use the repository directly.

## Cache key format

Cache keys are generated from:

1. Configured prefix (`arch.repository_cache.prefix`, default `arch_repo`)
2. Repository class basename
3. Target method name
4. MD5 hash of the serialized argument array

Example: `arch_repo:UserRepository:getOneByParams:f03f9a…`

## Clearing cache entries

Use the helper methods provided by `CacheWrapper`:

```php
// Remove cache for a specific argument combination
$userRepository->cache()->clearCache('getOneByParams', [
    ['email' => 'user@example.com'], // arguments array as received by the method
]);

// Flush the entire cache namespace (or the selected driver)
$userRepository->cache()->clearAllCache();
```

When `clearAllCache()` is called with a custom driver (`cache('redis')`), the underlying store is flushed. Without a custom driver the default cache store is cleared.

## Configuration

Settings are located in `config/arch.php`:

```php
'repository_cache' => [
    'enabled' => env('ARCH_REPOSITORY_CACHE_ENABLED', true),
    'ttl' => env('ARCH_REPOSITORY_CACHE_TTL', 3600),
    'prefix' => env('ARCH_REPOSITORY_CACHE_PREFIX', 'arch_repo'),
],
```

- **enabled** – toggle caching globally.
- **ttl** – time to live in seconds (default one hour).
- **prefix** – string used as the first segment of every cache key.

The helper honours the cache driver specified through Laravel's standard configuration. You may switch drivers per call by passing the driver name: `$repository->cache('redis')->getOneByParams([...])`.

## Behavioural notes

- Caching is skipped entirely when `arch.repository_cache.enabled` is set to `false`.
- Empty payloads for `cache()->paginateByParams()` or similar methods are still cached; choose parameters carefully to avoid generating too many unique keys.
- When arguments contain objects, they are serialized as provided. Prefer simple arrays where possible.

## Invalidating stale data

Because the proxy only caches read queries, it is your responsibility to invalidate cache entries after write operations:

```php
$user = $userRepository->getOneByParams(['email' => 'user@example.com']);
$user->update($data);

$userRepository->cache()->clearCache('getOneByParams', [
    ['email' => 'user@example.com'],
]);
```

Consider wrapping cache invalidation inside your service layer so the logic stays close to the write operation.

## Troubleshooting

- Ensure PCOV or another coverage driver is disabled when debugging cache issues; `Cache::flush()` is used when no driver is provided.
- When using the `array` cache driver, cached results exist only for the current request. Switch to a persistent store (`redis`, `memcached`, `file`, …) for long-lived entries.
- If you introduce new parameters to a repository method, remember to supply the same shape when calling `clearCache()`. The hash depends on both order and values.
