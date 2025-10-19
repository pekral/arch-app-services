# <img src="logo.svg" alt="Arch App Services Logo" width="200"/>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pekral/arch-app-services.svg?style=flat-square)](https://packagist.org/packages/pekral/arch-app-services)
[![Total Downloads](https://img.shields.io/packagist/dt/pekral/arch-app-services.svg?style=flat-square)](https://packagist.org/packages/pekral/arch-app-services)
[![Tests](https://img.shields.io/github/actions/workflow/status/pekral/arch-app-services/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/pekral/arch-app-services/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/pekral/arch-app-services?style=flat-square)](https://codecov.io/gh/pekral/arch-app-services)

> ⚠️ **This package is currently under active development.** The API may change in future versions. Use with caution in production environments.

**Arch App Services** - Clean architectural abstractions for building scalable Laravel applications

## Features

- **Action Pattern**: Clean, single-purpose classes for business logic
- **Action Logging**: Robust action execution logging with fallback mechanism
- **Repository Pattern**: Database query abstraction with pagination support  
- **Repository Caching**: Automatic caching layer for repository methods with configurable TTL
- **Model Manager**: CRUD operations with batch processing capabilities
- **Data Builder**: Pipeline-based data transformation using Laravel Pipeline
- **Data Validation**: Integrated validation using Laravel's validation system
- **Service Layer**: Combines Repository and Model Manager for complete CRUD operations
- **Type Safety**: Full PHPDoc type annotations and generics support
- **Laravel 12+ Ready**: Built for modern Laravel features and conventions
- **100% Test Coverage**: Comprehensive test suite ensuring reliability

## Installation

You can install the package via composer:

```bash
composer require pekral/arch-app-services
```

The package will automatically register its service provider.

Optionally, you can publish the configuration file:

```bash
php artisan vendor:publish --tag="arch-config"
```

## Architecture Overview

This package provides a clean architecture with the following components:

1. **Actions**: Single-purpose classes that handle specific business operations
2. **Action Logging**: Robust logging system with configurable channels and fallback
3. **Services**: Combine Repository and Model Manager for complete CRUD operations
4. **Repositories**: Handle read operations with advanced querying capabilities and caching
5. **Model Managers**: Handle write operations (create, update, delete)
6. **Data Builder**: Transform data using pipeline pattern
7. **Data Validator**: Integrated validation using Laravel's validation system
8. **Pipes**: Reusable data transformation components

## Usage Examples

### Creating a Repository

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

### Using Fluent Query Interface

The repository provides a fluent interface for building complex queries:

```php
<?php

namespace App\Repositories;

use Pekral\Arch\Repository\Mysql\BaseRepository;
use App\Models\User;

final class UserRepository extends BaseRepository
{
    protected function getModelClassName(): string
    {
        return User::class;
    }
    
    public function findActiveUsers(): Collection
    {
        return $this->query()
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }
    
    public function findUsersWithPosts(): Collection
    {
        return $this->query()
            ->whereHas('posts')
            ->with('posts')
            ->get();
    }
    
    public function searchUsers(string $term): Collection
    {
        return $this->query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
            })
            ->get();
    }
}

// Usage in Actions or Services
$users = $userRepository->query()
    ->where('active', true)
    ->whereIn('role', ['admin', 'moderator'])
    ->with(['posts', 'profile'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

### Using Repository Caching

The repository provides automatic caching through the `CacheableRepository` trait:

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

// Clear specific cache entry
$this->userRepository->cache()->clearCache('getOneByParams', $filters);

// Clear all cache entries (use with caution)
$this->userRepository->cache()->clearAllCache();
```

### Creating a Model Manager

```php
<?php

namespace App\Services;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use App\Models\User;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\App\Models\User>
 */
final class UserModelManager extends BaseModelManager
{
    protected function getModelClassName(): string
    {
        return User::class;
    }
}
```

### Creating a Service

```php
<?php

namespace App\Services;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Service\BaseModelService;
use App\Models\User;

/**
 * @extends \Pekral\Arch\Service\BaseModelService<\App\Models\User>
 */
final readonly class UserModelService extends BaseModelService
{
    public function __construct(
        private UserModelManager $userModelManager,
        private UserRepository $userRepository
    ) {
    }

    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function getModelManager(): BaseModelManager
    {
        return $this->userModelManager;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->userRepository;
    }
}
```

### Creating Actions with Data Transformation and Validation

Actions are single-purpose classes that handle specific business operations. They can use DataBuilder for transformation and DataValidator for validation:

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\DataValidation\DataValidator;
use App\Actions\User\Pipes\LowercaseEmailPipe;
use App\Actions\User\Pipes\UcFirstNamePipe;
use App\Services\UserModelService;
use App\Models\User;

final readonly class CreateUser
{
    use DataBuilder;
    use DataValidator;

    public function __construct(
        private UserModelService $userModelService,
        private VerifyUserAction $verifyUserAction,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(array $data): User
    {
        // Validate input data
        $this->validate($data, [
            'email' => 'required|email',
            'name' => 'required|string',
        ], []);
        
        // Transform data using pipeline
        $normalizedData = $this->build($data, [
            'email' => LowercaseEmailPipe::class,
            'name' => UcFirstNamePipe::class,
        ]);
        
        // Create user
        $user = $this->userModelService->create($normalizedData);
        
        // Send verification email
        $this->verifyUserAction->handle($user);
        
        return $user;
    }
}
```

### Creating Data Transformation Pipes

```php
<?php

namespace App\Actions\User\Pipes;

interface BuilderPipe
{
    /**
     * Transform user data.
     *
     * @param array<string, mixed> $data
     * @param callable(array<string, mixed>): array<string, mixed> $next
     * @return array<string, mixed>
     */
    public function handle(array $data, callable $next): array;
}

final readonly class LowercaseEmailPipe implements BuilderPipe
{
    public function handle(array $data, callable $next): array
    {
        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        return $next($data);
    }
}

final readonly class UcFirstNamePipe implements BuilderPipe
{
    public function handle(array $data, callable $next): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = str($data['name'])->lower()->ucfirst()->value();
        }

        return $next($data);
    }
}
```

### Using Actions in Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Actions\User\CreateUser;
use App\Actions\User\GetUser;
use App\Actions\User\UpdateUserName;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function store(Request $request, CreateUser $createUser)
    {
        $user = $createUser->execute($request->validated());
        
        return response()->json($user, 201);
    }
    
    public function show(Request $request, GetUser $getUser)
    {
        $user = $getUser->handle($request->only(['email', 'name']));
        
        return response()->json($user);
    }
    
    public function updateName(Request $request, UpdateUserName $updateUserName)
    {
        $user = User::findOrFail($request->user_id);
        $updateUserName->handle($request->name, $user);
        
        return response()->json(['message' => 'Name updated successfully']);
    }
}
```

## Available Methods

### Repository Methods

- `paginateByParams(array $params, array $with = [], ?int $itemsPerPage = null, array $orderBy = [], array $groupBy = [])` - Paginate results
- `getOneByParams(Collection|array $params, array $with = [], array $orderBy = [])` - Get one record or throw exception
- `findOneByParams(Collection|array $params, array $with = [], array $orderBy = [])` - Find one record or return null
- `countByParams(Collection|array $params, array $groupBy = [])` - Count records
- `query()` - Start a fluent query builder interface
- `createQueryBuilder()` - Create a new query builder instance
- `cache()` - Get cache wrapper for automatic caching

### Repository Caching Methods

- `cache()->paginateByParams(...)` - Cached pagination
- `cache()->getOneByParams(...)` - Cached single record retrieval
- `cache()->findOneByParams(...)` - Cached single record find
- `cache()->countByParams(...)` - Cached count
- `cache()->clearCache(string $method, array $args)` - Clear specific cache entry
- `cache()->clearAllCache()` - Clear all cache entries

### Model Manager Methods

- `create(array $data)` - Create single record
- `bulkCreate(array $dataArray)` - Bulk create records
- `bulkUpdate(array $dataArray, string $keyColumn = 'id')` - Bulk update records
- `deleteByParams(array $parameters)` - Delete by parameters

### Service Methods (Combines Repository + Model Manager)

**CRUD Operations:**
- `create(array $data)` - Create with validation
- `updateModel(Model $model, array $data)` - Update existing model
- `deleteModel(Model $model)` - Delete model
- `deleteByParams(array $parameters)` - Delete by parameters

**Read Operations:**
- `findOneByParams(array $parameters, array $with = [], array $orderBy = [])` - Find one or null
- `getOneByParams(array $parameters, array $with = [], array $orderBy = [])` - Find one or throw exception
- `paginateByParams(array $parameters = [], array $with = [], ?int $perPage = null, array $orderBy = [], array $groupBy = [])` - Paginate
- `countByParams(array $parameters, array $groupBy = [])` - Count records

**Bulk Operations:**
- `bulkCreate(array $data)` - Bulk create records
- `bulkUpdate(array $data, string $keyColumn = 'id')` - Bulk update records

## Data Builder Usage

The Data Builder uses Laravel's Pipeline to transform data through a series of pipes. It supports both general pipes (applied to all data) and specific pipes (applied to specific fields).

### Basic Usage

```php
use Pekral\Arch\DataBuilder\DataBuilder;

final readonly class ProcessUserData
{
    public function __construct(private DataBuilder $dataBuilder)
    {
    }
    
    public function handle(array $userData): array
    {
        return $this->dataBuilder->build($userData, [
            // General pipes (applied to all data)
            ValidateEmailPipe::class,
            SanitizeDataPipe::class,
            
            // Specific pipes (applied to specific fields)
            'email' => LowercaseEmailPipe::class,
            'name' => UcFirstNamePipe::class,
        ]);
    }
}
```

### Pipe Processing Order

1. **General Pipes**: Applied first to all data
2. **Specific Pipes**: Applied after general pipes to specific fields

### Using in Actions

```php
final readonly class CreateUser
{
    use DataBuilder;
    
    public function execute(array $data): User
    {
        $dataNormalized = $this->build($data, [
            'email' => LowercaseEmailPipe::class,
            'name' => UcFirstNamePipe::class,
        ]);
        
        return $this->userModelService->create($dataNormalized);
    }
}
```

## Data Validator Usage

The Data Validator provides a simple interface for validating data using Laravel's validation system.

### Basic Usage

```php
use Pekral\Arch\DataValidation\DataValidator;

final readonly class ValidateUserData
{
    use DataValidator;
    
    public function validateUser(array $data): array
    {
        return $this->validate($data, [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'name.required' => 'Name is required.',
        ]);
    }
}
```

### Using in Actions

```php
final readonly class CreateUser
{
    use DataValidator;
    
    public function execute(array $data): User
    {
        $this->validate($data, [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
        ], []);
        
        return $this->userModelService->create($data);
    }
}
```

### Validation Exception Handling

```php
try {
    $validatedData = $this->validate($data, $rules, $messages);
} catch (\Illuminate\Validation\ValidationException $e) {
    $errors = $e->errors();
    // Handle validation errors
}
```

## Action Logging Usage

The Action Logger provides robust logging for action execution with automatic fallback mechanism. It logs action start, success, and failure events with configurable logging channels.

### Basic Usage

```php
use Pekral\Arch\Action\ActionLogger;

final readonly class ProcessOrderAction
{
    use ActionLogger;
    
    public function execute(array $orderData): Order
    {
        $startTime = microtime(true);
        
        $this->logActionStart('ProcessOrder', [
            'order_id' => $orderData['id'] ?? null,
            'customer_id' => $orderData['customer_id'] ?? null
        ]);
        
        try {
            $order = $this->orderService->process($orderData);
            
            $this->logActionSuccess('ProcessOrder', [
                'order_id' => $order->id,
                'total_amount' => $order->total_amount,
                'processing_time' => microtime(true) - $startTime
            ]);
            
            return $order;
        } catch (\Exception $e) {
            $this->logActionFailure('ProcessOrder', $e->getMessage(), [
                'order_data' => $orderData,
                'error_type' => get_class($e),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
```

### Available Methods

- `logActionStart(string $action, array $context = [])` - Log action start
- `logActionSuccess(string $action, array $context = [])` - Log successful completion  
- `logActionFailure(string $action, string $error, array $context = [])` - Log failure

### Logging Configuration

Configure action logging in your `config/arch.php` file:

```php
return [
    // ... other config
    
    'action_logging' => [
        'channel' => env('ARCH_ACTION_LOG_CHANNEL', 'stack'),
        'enabled' => env('ARCH_ACTION_LOGGING_ENABLED', true),
    ],
];
```

### Environment Variables

```bash
# Use specific logging channel for actions
ARCH_ACTION_LOG_CHANNEL=actions

# Disable action logging
ARCH_ACTION_LOGGING_ENABLED=false
```

### Custom Logging Channel

Create a dedicated logging channel in `config/logging.php`:

```php
'channels' => [
    'actions' => [
        'driver' => 'daily',
        'path' => storage_path('logs/actions.log'),
        'level' => 'info',
        'days' => 14,
    ],
],
```

### Fallback Mechanism

If the primary logger fails, ActionLogger automatically falls back to writing detailed error information to `storage/logs/arch.log`:

```
[2025-01-15 14:30:25] ARCH FALLBACK LOG
Level: INFO
Action: ProcessOrder
Type: start
Original Message: Action started: ProcessOrder
Context: {
    "order_id": 12345,
    "customer_id": 67890
}
Logging Error: Redis connection timeout
Logging Error File: /app/vendor/predis/Client.php:156
Stack Trace: #0 /app/vendor/predis/Client.php...
--------------------------------------------------------------------------------
```

This ensures that **no action execution is interrupted** by logging failures, while still providing complete debugging information.

## Configuration

The package publishes a configuration file with the following options:

```php
return [
    'default_items_per_page' => 15,
    
    'exceptions' => [
        'should_not_happen' => \RuntimeException::class,
    ],
    
    'action_logging' => [
        'channel' => env('ARCH_ACTION_LOG_CHANNEL', 'stack'),
        'enabled' => env('ARCH_ACTION_LOGGING_ENABLED', true),
    ],
    
    'repository_cache' => [
        'enabled' => env('ARCH_REPOSITORY_CACHE_ENABLED', true),
        'ttl' => env('ARCH_REPOSITORY_CACHE_TTL', 3600), // 1 hour default
        'prefix' => env('ARCH_REPOSITORY_CACHE_PREFIX', 'arch_repo'),
    ],
];
```

### Environment Variables

```bash
# Action logging configuration
ARCH_ACTION_LOG_CHANNEL=actions
ARCH_ACTION_LOGGING_ENABLED=true

# Repository caching configuration
ARCH_REPOSITORY_CACHE_ENABLED=true
ARCH_REPOSITORY_CACHE_TTL=3600
ARCH_REPOSITORY_CACHE_PREFIX=arch_repo
```

## Testing

The package includes comprehensive tests with 100% code coverage:

```bash
composer test
composer coverage
```

## Exception Handling

The package provides a custom exception for unexpected situations:

```php
use Pekral\Arch\Exceptions\ShouldNotHappen;

// Throw exception with reason
throw ShouldNotHappen::because('This should never happen in normal circumstances');
```

## Contributing

This package is under active development. Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

**Note**: Since this package is still in development, please check the latest changes before implementing in production environments.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Petr Král](https://github.com/pekral)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.