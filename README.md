<div align="left">
    <img src="logo.svg" alt="Arch App Services — an arch connecting separate application layers" width="720">

<h1>Arch App Services</h1>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pekral/arch-app-services.svg?style=flat-square)](https://packagist.org/packages/pekral/arch-app-services)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Checkers](https://img.shields.io/github/actions/workflow/status/pekral/arch-app-services/checkers.yml?branch=master&label=checkers&style=flat-square)](https://github.com/pekral/arch-app-services/actions/workflows/checkers.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/pekral/arch-app-services.svg?style=flat-square)](https://packagist.org/packages/pekral/arch-app-services)

</div>

**Arch App Services** gives Laravel applications single-purpose Actions, Repositories for reads, and Model Managers for writes. Model Services combine the read and write layers; Artisan generators and custom PHPStan rules help keep that structure consistent.

> [!NOTE]
> The package is under active development and its API may change between releases. Review the [changelog](CHANGELOG.md) before upgrading.

## Requirements

- PHP `^8.4` with the SimpleXML extension.
- Laravel 12 or 13 (`illuminate/*` constraints: `^12.0 || ^13.0`).

## Installation

```bash
composer require pekral/arch-app-services
```

Laravel discovers `ArchServiceProvider` automatically.

DynamoDB support is optional. Applications using the DynamoDB Repositories and Model Managers must also install the driver:

```bash
composer require "baopham/dynamodb:^7.1"
```

SQL-only applications do not need this dependency. See [DynamoDB setup](docs/dynamodb-model.md) for models and configuration.

## Configuration

No configuration is required: defaults for pagination, repository caching and transaction attempts load from [`config/arch.php`](config/arch.php). Publish the file to change them:

```bash
php artisan vendor:publish --tag="arch-config"
```

Publish the generator templates to customize generated classes:

```bash
php artisan vendor:publish --tag="arch-stubs"
```

## Quick start

In a Laravel application with an existing `App\Models\User` model and migrated `users` table, generate its service stack:

```bash
php artisan make:arch-service "App\Models\User"
```

This creates `UserModelService`, `UserRepository` and `UserModelManager` in `app/Services/User`. The model must allow mass assignment of the fields you pass to `create()`; the example uses `name`, `email` and `password`.

Resolve the generated service through Laravel's container to write and read a user:

```php
use App\Services\User\UserModelService;
use Illuminate\Support\Facades\Hash;

$users = app(UserModelService::class);

$user = $users->create([
    'name' => 'Alex',
    'email' => 'alex@example.com',
    'password' => Hash::make('example-password'),
]);

$found = $users->getOneByParams(['email' => 'alex@example.com']);
// $found->id === $user->id
```

`create()` delegates to the Model Manager; `getOneByParams()` delegates to the Repository and throws `ModelNotFoundException` when no record matches. Use `findOneByParams()` for a nullable result.

### Put an operation in an Action

Save this as `app/Actions/User/UpdateUserName.php`. It follows the package's `final readonly` Action convention and injects the generated service:

```php
<?php

namespace App\Actions\User;

use App\Models\User;
use App\Services\User\UserModelService;
use Pekral\Arch\Action\ArchAction;

final readonly class UpdateUserName implements ArchAction
{
    public function __construct(private UserModelService $users)
    {
    }

    public function __invoke(string $name, User $user): void
    {
        $this->users->updateModel($user, ['name' => $name]);
    }
}
```

Call the Action from a controller or another application entry point after validating and authorizing the request:

```php
use App\Actions\User\UpdateUserName;

$updateUserName = app(UpdateUserName::class);
($updateUserName)('Alex Morgan', $user);
```

This Action takes its inputs as `__invoke()` arguments. The optional `ActionInvokeArgumentsRule` PHPStan rule reports that style; see the [rule summary](docs/phpstan-rules.md#rule-summary) before enabling it.

## What it does

| Component | Responsibility |
| --- | --- |
| Actions & Model Services | Actions orchestrate an operation through `__invoke()`; Model Services combine reads and writes |
| Repositories & Model Managers | Query and paginate records; create, update, delete and process records in bulk, with SQL and DynamoDB implementations |
| Repository caching | Opt in to cached reads through `CacheableRepository::cache()`, with configurable TTL and explicit invalidation |
| Data objects & validation | Build DTOs on Spatie Laravel Data, validate input and transform data through pipelines |
| Transactions & Results | Wrap SQL writes with `Transactional`, `#[InTransaction]` helpers or nested savepoints; return explicit success/failure outcomes with `Result` |
| Architecture rules & generators | Generate Actions, service stacks, DTOs and validation rules; check layer boundaries, immutable dependencies and raw SQL bindings with PHPStan |

The `#[InTransaction]` attribute requires the `TransactionAwareAction` trait and an explicit call to `executeWithTransactionAttribute()` inside the Action. The attribute alone does not wrap execution. See the [transaction example](examples/Actions/User/TransferUserDataAction.php).

The package ships 24 PHPStan rules in the `Pekral\Arch\PHPStan\Rules` namespace. Installation alone does not enable them; register the rules you want in your PHPStan configuration. The [rule summary](docs/phpstan-rules.md#rule-summary) lists each rule with a reported example.

## Documentation

| Topic | Guide |
| --- | --- |
| Scaffolding | [Artisan commands, options and custom stubs](docs/code-generation.md) |
| Data access | [Model Managers and bulk writes](docs/model-manager.md) · [Query Builder methods](docs/query-builder-methods.md) · [Soft deletes](docs/soft-delete.md) |
| Cached reads | [Caching, keys and invalidation](docs/repository-caching.md) |
| Input and transformation | [DTOs and validation](docs/validation.md) · [Data Builder pipelines](docs/data-builder.md) |
| DynamoDB | [Models and setup](docs/dynamodb-model.md) |
| Architecture checks | [PHPStan rule summary](docs/phpstan-rules.md#rule-summary) · [Registration](docs/phpstan-rules.md#configuration) |
| Transactions | [Atomic writes](examples/Actions/User/CreateUserWithTransaction.php) · [Attribute helper](examples/Actions/User/TransferUserDataAction.php) · [Nested savepoints](examples/Actions/User/CreateUserWithNestedTransaction.php) |
| Expected failures | [Result-based Action](examples/Actions/User/CreateUserWithResult.php) · [Result API](src/Result/Result.php) |

More working patterns are in [`examples/`](examples).

## Testing

Repository development uses PHP 8.5, matching CI. The test suite uses SQLite and DynamoDB Local; `composer test:coverage` starts the local Docker Compose service through the bundled script. Coverage requires PCOV.

```bash
composer install
composer test:coverage
```

| Command | Checks |
| --- | --- |
| `composer analyse` | PHPStan static analysis |
| `composer security-audit` | Dependencies against known security advisories |
| `composer check` | Composer normalization, PHPCS, Pint, Rector dry run, PHPStan, dependency audit and tests with a 100% coverage threshold |

[Checkers](https://github.com/pekral/arch-app-services/actions/workflows/checkers.yml) runs on pushes and pull requests to `master`/`main`, and weekly.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history and breaking changes.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Report bugs and request features through [GitHub issues](https://github.com/pekral/arch-app-services/issues).

Report security vulnerabilities privately as described in the [security policy](.github/SECURITY.md). Everyone taking part is expected to follow the [Code of Conduct](.github/CODE_OF_CONDUCT.md).

## License

MIT. See [LICENSE](LICENSE).
