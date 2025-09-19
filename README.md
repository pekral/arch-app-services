# Laravel Arch App Services

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pekral/arch-app-services.svg?style=flat-square)](https://packagist.org/packages/pekral/arch-app-services)
[![Total Downloads](https://img.shields.io/packagist/dt/pekral/arch-app-services.svg?style=flat-square)](https://packagist.org/packages/pekral/arch-app-services)

Laravel package providing architectural abstractions for services, repositories and model managers with support for modern PHP and Laravel features.

## Features

- **Abstract Facade Pattern**: Provides consistent interface for service layers
- **Repository Pattern**: Database query abstraction with pagination support  
- **Model Manager**: CRUD operations with batch processing capabilities
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
use Pekral\Arch\Repository\Mysql\AbstractRepository;

final class UserRepository extends AbstractRepository
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
use Pekral\Arch\ModelManager\Mysql\AbstractModelManager;

final class UserModelManager extends AbstractModelManager
{
    protected function getModelClassName(): string
    {
        return User::class;
    }
}
```

### Creating a Service Facade

```php
<?php

namespace App\Services;

use Pekral\Arch\Service\Service;
use App\Repositories\UserRepository;

final class UserService extends Service
{
    public function __construct(
        private readonly UserModelManager $modelManager,
        private readonly UserRepository $repository,
    ) {}

    protected function getModelManager(): UserModelManager
    {
        return $this->modelManager;
    }

    protected function getRepository(): UserRepository
    {
        return $this->repository;
    }
}
```

### Available Methods

#### Repository Methods

- `getPaginated(Collection|array $params, array $withRelations = [], int $itemsPerPage = 15)`
- `getOneByParams(Collection|array $params)` - throws ModelNotFoundException
- `findOneByParams(Collection|array $params, array $with = [])` - returns null if not found
- `allByParams(Collection|array $params, array $with = [])`

#### Model Manager Methods

- `createOrUpdate(Collection|array $data, ?Model $model)`
- `create(Collection $data)`
- `deleteByKeys(Collection $keys)`
- `batchInsert(Collection $data, bool $insertOrIgnore)`
- `updateByKeys(Collection $data, array $keys)`

#### Facade Methods

Combines repository and model manager functionality with automatic parameter conversion.

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
