# <img src="logo.svg" alt="Larach Logo" width="200"/>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pekral/larach.svg?style=flat-square)](https://packagist.org/packages/pekral/larach)
[![Total Downloads](https://img.shields.io/packagist/dt/pekral/larach.svg?style=flat-square)](https://packagist.org/packages/pekral/larach)
[![Tests](https://img.shields.io/github/actions/workflow/status/pekral/larach/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/pekral/larach/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/pekral/larach?style=flat-square)](https://codecov.io/gh/pekral/larach)

> ⚠️ **This package is currently under active development.** The API may change in future versions. Use with caution in production environments.

**Laravel Architecture Stack** - Clean architectural abstractions for building scalable applications

## Features

- **Action Pattern**: Clean, single-purpose classes for business logic
- **Repository Pattern**: Database query abstraction with pagination support  
- **Model Manager**: CRUD operations with batch processing capabilities
- **Data Builder**: Pipeline-based data transformation using Laravel Pipeline
- **Service Layer**: Combines Repository and Model Manager for complete CRUD operations
- **Type Safety**: Full PHPDoc type annotations and generics support
- **Laravel 11+ Ready**: Built for modern Laravel features and conventions
- **100% Test Coverage**: Comprehensive test suite ensuring reliability

## Installation

You can install the package via composer:

```bash
composer require pekral/larach
```

The package will automatically register its service provider.

Optionally, you can publish the configuration file:

```bash
php artisan vendor:publish --tag="larach-config"
```

## Architecture Overview

This package provides a clean architecture with the following components:

1. **Actions**: Single-purpose classes that handle specific business operations
2. **Services**: Combine Repository and Model Manager for complete CRUD operations
3. **Repositories**: Handle read operations with advanced querying capabilities
4. **Model Managers**: Handle write operations (create, update, delete)
5. **Data Builder**: Transform data using pipeline pattern
6. **Pipes**: Reusable data transformation components

## Usage Examples

### Creating a Repository

```php
<?php

namespace App\Repositories;

use Pekral\Arch\Repository\Mysql\BaseRepository;
use App\Models\User;

/**
 * @extends \Pekral\Arch\Repository\Mysql\BaseRepository<\App\Models\User>
 */
final class UserRepository extends BaseRepository
{
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

### Creating Actions

Actions are single-purpose classes that handle specific business operations:

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;
use App\Actions\User\Pipes\LowercaseEmailPipe;
use App\Actions\User\Pipes\UcFirstNamePipe;
use App\Services\UserModelService;
use App\Models\User;

final readonly class CreateUser
{
    public function __construct(
        private UserModelService $userModelService,
        private DataBuilder $dataBuilder,
        private VerifyUserAction $verifyUserAction,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): User
    {
        // Transform data using pipeline
        $normalizedData = $this->dataBuilder->build($data, [
            LowercaseEmailPipe::class,
            UcFirstNamePipe::class
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

## Configuration

The package publishes a configuration file with the following options:

```php
return [
    'default_items_per_page' => 15,
    'exceptions' => [
        'should_not_happen' => \RuntimeException::class,
    ],
];
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