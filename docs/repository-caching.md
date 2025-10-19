# Repository Caching

The package provides automatic caching functionality for repository methods through the `CacheableRepository` trait and `CacheWrapper` class. This allows you to cache expensive database queries automatically with configurable TTL and cache keys.

## Architecture

### Main Components

1. **CacheableRepository Trait** - Adds caching capability to repositories
2. **CacheWrapper Class** - Proxy class that handles automatic caching
3. **Configuration** - Configurable TTL, prefix, and enable/disable options

### How It Works

The caching system uses Laravel's cache system with automatically generated cache keys based on:
- Repository class name
- Method name
- Method arguments (serialized and hashed)

## Basic Usage

### Adding Caching to Repository

```php
<?php

namespace App\Repositories;

use Pekral\Arch\Repository\CacheableRepository;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use App\Models\User;

/**
 * @extends \Pekral\Arch\Repository\Mysql\BaseRepository<\App\Models\User>
 */
final class UserRepository extends BaseRepository
{
    use CacheableRepository;

    protected function getModelClassName(): string
    {
        return User::class;
    }
}
```

### Using Cached Repository Methods

```php
<?php

namespace App\Actions\User;

use App\Repositories\UserRepository;
use App\Models\User;

final readonly class GetUserCached
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(array $filters): User
    {
        // Automatically cached for configured TTL
        return $this->userRepository->cache()->getOneByParams($filters);
    }
}
```

### Cached Methods

All repository methods are automatically cached when using the `cache()` wrapper:

```php
// Cached pagination
$users = $userRepository->cache()->paginateByParams([
    'active' => true
], ['posts'], 15);

// Cached single record retrieval
$user = $userRepository->cache()->getOneByParams([
    'email' => 'user@example.com'
]);

// Cached count
$count = $userRepository->cache()->countByParams([
    'role' => 'admin'
]);

// Cached find
$user = $userRepository->cache()->findOneByParams([
    'name' => 'John Doe'
]);
```

## Cache Management

### Clearing Specific Cache Entries

```php
// Clear cache for specific method and parameters
$userRepository->cache()->clearCache('getOneByParams', [
    'email' => 'user@example.com'
]);

// Clear cache for pagination with specific parameters
$userRepository->cache()->clearCache('paginateByParams', [
    'active' => true
], ['posts'], 15);
```

### Clearing All Cache

```php
// Clear all cache entries (use with caution)
$userRepository->cache()->clearAllCache();
```

### Cache Key Generation

Cache keys are automatically generated using:
- Repository class name (basename)
- Method name
- Serialized and MD5-hashed arguments
- Configurable prefix

Example cache key:
```
arch_repo:UserRepository:getOneByParams:a1b2c3d4e5f6...
```

## Configuration

### Basic Configuration

Configure caching in `config/arch.php`:

```php
return [
    'repository_cache' => [
        'enabled' => env('ARCH_REPOSITORY_CACHE_ENABLED', true),
        'ttl' => env('ARCH_REPOSITORY_CACHE_TTL', 3600), // 1 hour default
        'prefix' => env('ARCH_REPOSITORY_CACHE_PREFIX', 'arch_repo'),
    ],
];
```

### Environment Variables

```bash
# Enable/disable repository caching
ARCH_REPOSITORY_CACHE_ENABLED=true

# Cache TTL in seconds (default: 3600 = 1 hour)
ARCH_REPOSITORY_CACHE_TTL=3600

# Cache key prefix (default: arch_repo)
ARCH_REPOSITORY_CACHE_PREFIX=arch_repo
```

### Laravel Cache Configuration

Make sure you have a proper cache driver configured in `config/cache.php`:

```php
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
    
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
    ],
],
```

## Advanced Usage

### Conditional Caching

```php
<?php

namespace App\Actions\User;

use App\Repositories\UserRepository;
use App\Models\User;

final readonly class GetUserWithConditionalCache
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(array $filters, bool $useCache = true): User
    {
        if ($useCache) {
            return $this->userRepository->cache()->getOneByParams($filters);
        }
        
        // Bypass cache
        return $this->userRepository->getOneByParams($filters);
    }
}
```

### Cache Invalidation Strategies

```php
<?php

namespace App\Actions\User;

use App\Repositories\UserRepository;
use App\Models\User;

final readonly class UpdateUserAndInvalidateCache
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function handle(User $user, array $data): User
    {
        // Update user
        $user->update($data);
        
        // Invalidate related cache entries
        $this->userRepository->cache()->clearCache('getOneByParams', [
            'id' => $user->id
        ]);
        
        $this->userRepository->cache()->clearCache('getOneByParams', [
            'email' => $user->email
        ]);
        
        return $user;
    }
}
```

### Using with Actions

```php
<?php

namespace App\Actions\User;

use App\Repositories\UserRepository;
use App\Models\User;

final readonly class GetUsersCached
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->userRepository->cache()->paginateByParams(
            $filters,
            ['posts', 'profile'],
            20,
            ['created_at' => 'desc']
        );
    }
}
```

## Performance Considerations

### Cache Hit Optimization

```php
// Good: Specific cache keys
$user = $userRepository->cache()->getOneByParams([
    'email' => 'specific@example.com'
]);

// Avoid: Too generic parameters that might not hit cache
$users = $userRepository->cache()->paginateByParams([
    'created_at' => '>', now()->subDays(1) // This creates new cache key every time
]);
```

### Memory Usage

```php
// For large datasets, consider shorter TTL
// In config/arch.php
'repository_cache' => [
    'ttl' => 300, // 5 minutes for large datasets
],
```

### Cache Warming

```php
<?php

namespace App\Console\Commands;

use App\Repositories\UserRepository;
use Illuminate\Console\Command;

class WarmUserCache extends Command
{
    protected $signature = 'cache:warm-users';
    
    public function handle(UserRepository $userRepository): int
    {
        // Warm cache for frequently accessed data
        $userRepository->cache()->paginateByParams(['active' => true]);
        $userRepository->cache()->countByParams(['role' => 'admin']);
        
        $this->info('User cache warmed successfully.');
        
        return 0;
    }
}
```

## Monitoring and Debugging

### Cache Statistics

```php
use Illuminate\Support\Facades\Cache;

// Check cache driver
$driver = Cache::getDefaultDriver();
echo "Using cache driver: {$driver}";

// Check cache store
$store = Cache::store();
echo "Cache store: " . get_class($store);
```

### Debug Cache Keys

```php
// Add logging to see generated cache keys
Log::info('Cache key generated', [
    'repository' => 'UserRepository',
    'method' => 'getOneByParams',
    'args_hash' => md5(serialize($arguments))
]);
```

### Cache Miss Detection

```php
// Monitor cache performance
$startTime = microtime(true);
$result = $userRepository->cache()->getOneByParams($filters);
$endTime = microtime(true);

if ($endTime - $startTime > 0.1) {
    Log::warning('Slow cache operation detected', [
        'duration' => $endTime - $startTime,
        'method' => 'getOneByParams'
    ]);
}
```

## Best Practices

1. **Use specific cache keys** - Avoid overly generic parameters
2. **Set appropriate TTL** - Balance between performance and data freshness
3. **Invalidate cache on updates** - Clear related cache entries when data changes
4. **Monitor cache performance** - Track hit rates and response times
5. **Use different TTL for different data types** - Critical data vs. frequently changing data
6. **Test cache behavior** - Ensure cache invalidation works correctly

## Troubleshooting

### Cache Not Working

```php
// Check if caching is enabled
if (!config('arch.repository_cache.enabled')) {
    echo "Repository caching is disabled";
}

// Check cache driver
if (Cache::getDefaultDriver() === 'array') {
    echo "Using array cache driver - cache won't persist";
}
```

### Memory Issues

```php
// Reduce TTL for memory-intensive operations
'repository_cache' => [
    'ttl' => 60, // 1 minute for large datasets
],
```

### Cache Key Conflicts

```php
// Use unique prefixes for different environments
'repository_cache' => [
    'prefix' => 'arch_repo_' . app()->environment(),
],
```
