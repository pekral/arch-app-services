# Laravel Arch App Services

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pekral/arch-app-services.svg?style=flat-square)](https://packagist.org/packages/pekral/arch-app-services)
[![Total Downloads](https://img.shields.io/packagist/dt/pekral/arch-app-services.svg?style=flat-square)](https://packagist.org/packages/pekral/arch-app-services)

Laravel package providing architectural abstractions for services, repositories and model managers with support for modern PHP and Laravel features.

## Features

- **Abstract Facade Pattern**: Provides consistent interface for service layers
- **Repository Pattern**: Database query abstraction with pagination support  
- **Model Manager**: CRUD operations with batch processing capabilities
- **Validation Layer**: Integrated validation for all CRUD operations
- **Soft Delete Support**: Complete soft delete functionality with restore capabilities
- **Type Safety**: Full PHPDoc type annotations and generics support
- **Laravel 12 Ready**: Built for latest Laravel features and conventions

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

## Usage

### Creating a Repository

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Pekral\Arch\Repository\Mysql\BaseRepository;

final class UserRepository extends BaseRepository
{
    protected function getModelClassName(): string
    {
        return User::class;
    }
}
```

### Creating a Model Manager

```php
<?php

namespace App\Services;

use App\Models\User;
use Pekral\Arch\ModelManager\Mysql\BaseModelManager;

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

use App\Models\User;
use Pekral\Arch\Service\BaseModelService;

final class UserService extends BaseModelService
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function createModelManager(): UserModelManager
    {
        return new UserModelManager();
    }

    protected function createRepository(): UserRepository
    {
        return new UserRepository();
    }

    // Optional: Define validation rules
    protected function getCreateRules(): array
    {
        return [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ];
    }

    protected function getUpdateRules(): array
    {
        return [
            'email' => 'sometimes|email|unique:users,email,{id}',
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:6',
        ];
    }
}
```

### Available Methods

#### Repository Methods

- `paginateByParams(Collection|array $params, array $with = [], ?int $perPage = null, array $orderBy = [], array $groupBy = [])`
- `getOneByParams(Collection|array $params, array $with = [], array $orderBy = [])` - throws ModelNotFoundException
- `findOneByParams(Collection|array $params, array $with = [], array $orderBy = [])` - returns null if not found
- `findAllByParams(Collection|array $params, array $with = [], array $orderBy = [], array $groupBy = [], ?int $limit = null)`
- `countByParams(Collection|array $params, array $groupBy = [])`

#### Model Manager Methods

- `create(array $data)` - create single record
- `updateByParams(array $data, array $conditions)` - update by conditions
- `deleteByParams(array $parameters)` - delete by parameters
- `bulkCreate(array $dataArray)` - bulk create records
- `bulkUpdate(array $dataArray, string $keyColumn = 'id')` - bulk update records
- `softDeleteByParams(array $parameters)` - soft delete by parameters

#### Service Methods (Combines Repository + Model Manager)

**CRUD Operations:**
- `create(array|Collection $attributes, ?array $rules = null)` - create with validation
- `updateByParams(array|Collection $data, array $conditions, ?array $rules = null)` - update with validation
- `deleteByParams(array|Collection $parameters)` - delete by parameters

**Read Operations:**
- `findOneByParams(array|Collection $parameters, array $with = [], array $orderBy = [])` - find one or null
- `getOneByParams(array|Collection $parameters, array $with = [], array $orderBy = [])` - find one or throw exception
- `findAllByParams(array|Collection $parameters, array $with = [], array $orderBy = [], array $groupBy = [], ?int $limit = null)` - find all
- `paginateByParams(array|Collection $parameters = [], array $with = [], ?int $perPage = null, array $orderBy = [], array $groupBy = [])` - paginate
- `countByParams(array|Collection $parameters, array $groupBy = [])` - count records

**Soft Delete Operations:**
- `softDelete(int|string $id)` - soft delete by ID
- `softDeleteByParams(array|Collection $parameters)` - soft delete by parameters
- `restore(int|string $id)` - restore soft deleted record
- `restoreByParams(array|Collection $parameters)` - restore by parameters
- `forceDelete(int|string $id)` - permanent delete by ID
- `forceDeleteByParams(array|Collection $parameters)` - permanent delete by parameters

### Validation Example

```php
// Service with validation rules
final class UserService extends BaseModelService
{
    protected function getCreateRules(): array
    {
        return [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ];
    }

    protected function getUpdateRules(): array
    {
        return [
            'email' => 'sometimes|email|unique:users,email,{id}',
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:6',
        ];
    }
}

// Usage with automatic validation
$user = $userService->create([
    'email' => 'john@example.com',
    'name' => 'John Doe',
    'password' => 'password123',
]);

// Usage with custom rules
$user = $userService->create($data, [
    'email' => 'required|email',
    'name' => 'required|string',
]);
```

### Soft Delete Example

```php
// Soft delete by ID
$deleted = $userService->softDelete($user->id);

// Soft delete by parameters
$deletedCount = $userService->softDeleteByParams([
    'email' => 'old@example.com'
]);

// Restore soft deleted record
$restored = $userService->restore($user->id);

// Permanent delete
$deleted = $userService->forceDelete($user->id);
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

```bash
composer test
composer coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Petr Král](https://github.com/pekral)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
