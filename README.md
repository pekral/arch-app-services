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
- **Model Manager**: CRUD operations with batch processing and duplicate handling capabilities
- **Data Builder**: Pipeline-based data transformation using Laravel Pipeline
- **Data Validation**: Integrated validation using Laravel's validation system
- **Service Layer**: Combines Repository and Model Manager for complete CRUD operations
- **PHPStan Rules**: Custom architecture rules enforcing best practices
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

### Bulk Operations with Duplicate Handling

The Model Manager provides powerful bulk operations including duplicate handling:

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Tests\Models\User;

final readonly class BulkImportUsers
{
    public function __construct(
        private UserModelManager $userModelManager,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $userData
     * @return array{
     *     total_processed: int,
     *     created: int,
     *     ignored: int
     * }
     */
    public function execute(array $userData): array
    {
        if ($userData === []) {
            return [
                'total_processed' => 0,
                'created' => 0,
                'ignored' => 0,
            ];
        }

        // Prepare data with timestamps
        $preparedData = $this->prepareUserData($userData);

        // Count existing users before import
        $existingCount = User::count();

        // Use insertOrIgnore to handle duplicates
        $processedCount = $this->userModelManager->insertOrIgnore($preparedData);

        // Count users after import
        $newCount = User::count();
        $createdCount = $newCount - $existingCount;
        $ignoredCount = $processedCount - $createdCount;

        return [
            'total_processed' => $processedCount,
            'created' => $createdCount,
            'ignored' => $ignoredCount,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $userData
     * @return array<int, array<string, mixed>>
     */
    private function prepareUserData(array $userData): array
    {
        $now = now();

        return array_map(function (array $data) use ($now): array {
            return array_merge($data, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $userData);
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
- `insertOrIgnore(array $dataArray)` - Bulk insert records, ignoring duplicates based on unique constraints
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
- `insertOrIgnore(array $data)` - Bulk insert records, ignoring duplicates
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

## Check Action Coverage Command

The package includes a command to verify that all your Action classes have 100% test coverage. This ensures that your business logic is properly tested.

### Installation

After installing the package via composer:

```bash
composer require pekral/arch-app-services
```

The coverage checker command is automatically available in your project.

### Usage

Run the coverage check command from your Laravel project root:

```bash
./vendor/bin/arch-coverage app/Actions
```

You can specify any directory containing your Action classes:

```bash
./vendor/bin/arch-coverage app/Modules/Orders/Actions
./vendor/bin/arch-coverage src/Domain/User/Actions
```

### Integration with Composer Scripts

Add the command to your `composer.json` for easier access:

```json
{
    "scripts": {
        "test:action-coverage": "vendor/bin/arch-coverage app/Actions"
    }
}
```

Then run:

```bash
composer test:action-coverage
```

### What It Does

The command will:

1. **Scan the specified directory** in your Laravel application for all Action classes (classes implementing `ArchAction` interface)
2. **Automatically exclude** Command and Pipes directories (they are infrastructure, not business logic)
3. **Run your application's tests** with code coverage analysis for the Action classes
4. **Analyze the coverage report** and verify that each Action class has 100% coverage
5. **Report results** with a list of any Actions that don't meet the coverage threshold

### Example Output

**Success** (all Actions have 100% coverage):

```bash
Running Tests for Action Classes
================================

 [OK] All Action classes have 100% code coverage!
```

**Failure** (some Actions have insufficient coverage):

```bash
Running Tests for Action Classes
================================

 [ERROR] The following Action classes have less than 100% coverage:

  - CreateUser: 85.71%
  - UpdateUserName: 66.67%
```

### Integration with CI/CD

Add the coverage check to your Laravel application's CI/CD pipeline to ensure all Action classes maintain 100% coverage:

#### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: pcov
          
      - name: Install Dependencies
        run: composer install --prefer-dist --no-interaction
        
      - name: Run Application Tests
        run: php artisan test
        
      - name: Check Action Coverage
        run: ./vendor/bin/arch-coverage app/Actions
```

#### GitLab CI Example

```yaml
test:
  image: php:8.3
  before_script:
    - apt-get update && apt-get install -y git unzip
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --prefer-dist --no-interaction
  script:
    - php artisan test
    - ./vendor/bin/arch-coverage app/Actions
```

#### Bitbucket Pipelines Example

```yaml
pipelines:
  default:
    - step:
        name: Test
        image: php:8.3
        script:
          - apt-get update && apt-get install -y git unzip
          - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
          - composer install --prefer-dist --no-interaction
          - php artisan test
          - ./vendor/bin/arch-coverage app/Actions
```

### Requirements

- Laravel 10.x or 11.x (with Pest or PHPUnit)
- PHP 8.3 or higher
- PCOV or Xdebug extension for code coverage
- Actions must implement the `ArchAction` interface from this package

### How It Works

The command operates within your Laravel application context:

1. Uses your application's **PHPUnit/Pest configuration** (`phpunit.xml`)
2. Runs tests from your **test directory** (typically `tests/Unit/Actions`)
3. Generates a temporary **Clover XML coverage report**
4. Analyzes coverage for Action classes in the specified directory
5. Enforces **100% code coverage threshold** for all Actions
6. Automatically cleans up temporary files

### Configuration

The command respects your Laravel application's test configuration:

- **PHPUnit configuration**: Uses your `phpunit.xml` in the project root
- **Test directory**: Expects tests in `tests/Unit/Actions` (Laravel convention)
- **Coverage driver**: Uses PCOV by default (fastest), falls back to Xdebug
- **Coverage threshold**: Fixed at 100% (ensures business logic is fully tested)

### Best Practices

**1. Organize Your Actions**

```
app/
└── Actions/
    ├── User/
    │   ├── CreateUser.php
    │   ├── UpdateUser.php
    │   └── DeleteUser.php
    └── Order/
        ├── CreateOrder.php
        └── ProcessOrder.php
```

**2. Exclude Infrastructure Code**

The command automatically excludes:
- `/Command/` directories (Artisan commands)
- `/Pipes/` directories (data transformation pipes)

**3. Corresponding Tests Structure**

```
tests/
└── Unit/
    └── Actions/
        ├── User/
        │   ├── CreateUserTest.php
        │   ├── UpdateUserTest.php
        │   └── DeleteUserTest.php
        └── Order/
            ├── CreateOrderTest.php
            └── ProcessOrderTest.php
```

### Notes

- Only classes implementing the `ArchAction` interface are checked
- The command runs within your Laravel application's working directory
- Coverage reports are temporarily generated and automatically cleaned up
- All paths are relative to your Laravel application root

### Troubleshooting

**Command not found:**
```bash
# Make sure the package is installed
composer show pekral/arch-app-services

# If needed, make the binary executable
chmod +x vendor/bin/arch-coverage
```

**Coverage extension not installed:**
```bash
# Install PCOV (recommended - fastest)
pecl install pcov

# Or install Xdebug (provides more features but slower)
pecl install xdebug
```

**No Action classes found:**

Make sure:
1. Your Action classes implement the `ArchAction` interface from this package
2. Action classes are in the directory you specified (e.g., `app/Actions`)
3. You're not trying to check `/Command/` or `/Pipes/` subdirectories (automatically excluded)

**Coverage below 100%:**

The command will list which Actions need more test coverage:
```bash
[ERROR] The following Action classes have less than 100% coverage:

  - CreateUser: 85.71%
  - UpdateUserName: 66.67%
```

Write additional tests to cover the missing code paths in these Actions.

## PHPStan Architecture Rules

The package includes custom PHPStan rules that enforce architectural best practices:

### Available Rules

1. **NoEloquentStorageMethodsInActionsRule** - Prevents direct Eloquent storage method calls (`save()`, `create()`, `delete()`, etc.) in Action classes
2. **NoDirectDatabaseQueriesInActionsRule** - Prevents direct database query calls (`where()`, `find()`, `get()`, etc.) in Action classes
3. **OnlyModelManagersCanPersistDataRule** - Ensures data persistence operations are only performed in ModelManager or ModelService classes

### Why These Rules?

These rules enforce clean architecture by ensuring:
- Actions use Repository pattern for data retrieval
- Actions use ModelManager pattern for data persistence
- Database operations are properly separated by concerns

### Example Violations

```php
// ❌ Violation: Direct query in Action
final readonly class GetUsers implements ArchAction
{
    public function execute(): Collection
    {
        return User::where('active', true)->get(); // PHPStan error!
    }
}

// ✅ Correct: Using Repository
final readonly class GetUsers implements ArchAction
{
    public function __construct(private UserModelService $service) {}
    
    public function execute(): Collection
    {
        return $this->service->findByParams(['active' => true]);
    }
}
```

### Running Rules

The rules are automatically enforced when running PHPStan:

```bash
composer analyse
```

For detailed documentation about the PHPStan rules, see [PHPStan Rules Documentation](docs/phpstan-rules.md).

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