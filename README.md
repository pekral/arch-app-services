<div align="left">
    <img src="logo.svg" alt="Arch App Services — an arch connecting separate application layers" width="720">

<h1>Give Laravel business logic a clear structure</h1>

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

Defaults are loaded automatically. To customize pagination, repository caching or transaction attempts, publish [the configuration](config/arch.php):

```bash
php artisan vendor:publish --tag="arch-config"
```

| Setting | Default | Purpose |
| --- | --- | --- |
| `arch.default_items_per_page` | `15` | Repository pagination size |
| `arch.repository_cache.enabled` | `true` | Enable caching for reads made through `cache()` |
| `arch.repository_cache.ttl` | `3600` | Cache lifetime in seconds |
| `arch.repository_cache.prefix` | `arch_repo` | Cache key prefix |
| `arch.transactions.default_attempts` | `1` | Transaction attempts when none are passed explicitly |

Generator templates can also be published for customization:

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

## PHPStan rules

The package ships **24 rules** in the `Pekral\Arch\PHPStan\Rules` namespace. Register the rules you want in your PHPStan configuration; installation alone does not enable them. See [registration and configuration](docs/phpstan-rules.md#configuration).

Each example below is a shortened fragment that the rule **reports**, in the context described by its TL;DR. Rule names link to their implementation.

### Actions

#### [ActionsMustImplementArchActionRule](phpstan/Rules/ActionsMustImplementArchActionRule.php)

**TL;DR:** Classes in configured Action namespaces must implement `ArchAction`. Without configured namespaces, this rule reports nothing.

**Example reported:** In a configured `App\Actions\` namespace.

```php
class CreateUser {}
```

#### [ActionClassModifiersRule](phpstan/Rules/ActionClassModifiersRule.php)

**TL;DR:** Concrete, named `ArchAction` classes must be `final readonly`.

**Example reported:** Without `readonly`.

```php
final class CreateUser implements ArchAction
```

#### [ActionInvokeMethodRule](phpstan/Rules/ActionInvokeMethodRule.php)

**TL;DR:** An `ArchAction` must declare only one public method besides its constructor: `__invoke()` with an explicit return type.

**Example reported:** Instead of `__invoke()`.

```php
public function execute(): void
```

#### [ActionInvokeArgumentsRule](phpstan/Rules/ActionInvokeArgumentsRule.php)

**TL;DR:** Invoke an `ArchAction` without arguments; supply inputs through its constructor.

**Example reported:**

```php
$action($data)
```

**Preferred form:**

```php
$action()
```

#### [ActionReturnDtoRule](phpstan/Rules/ActionReturnDtoRule.php)

**TL;DR:** An `ArchAction::__invoke()` must not declare an array return type; use a Spatie Data DTO for structured output. Other non-array results, including `void`, remain allowed.

**Example reported:**

```php
public function __invoke(): array
```

#### [NoHttpResponseInActionsRule](phpstan/Rules/NoHttpResponseInActionsRule.php)

**TL;DR:** Actions return data; controllers build HTTP responses. Checks declared types and returned expressions, including responses hidden behind `mixed`.

**Example reported:** Inside an Action.

```php
return response()->json($data);
```

#### [NoHttpExceptionsInActionsRule](phpstan/Rules/NoHttpExceptionsInActionsRule.php)

**TL;DR:** Actions must not produce HTTP exceptions or call `abort*()`; throw domain exceptions for the entry point to translate.

**Example reported:** Inside an Action.

```php
abort(404);
```

#### [NoLaravelHelpersForActionsRule](phpstan/Rules/NoLaravelHelpersForActionsRule.php)

**TL;DR:** Inside an Action, inject other Actions through the constructor instead of resolving them with `app()`, `resolve()` or `make()`.

**Example reported:** Inside another Action.

```php
app(CreateUser::class)
```

#### [NoEloquentStorageMethodsInActionsRule](phpstan/Rules/NoEloquentStorageMethodsInActionsRule.php)

**TL;DR:** An Action must delegate Eloquent model writes to a Model Manager rather than call storage methods directly.

**Example reported:** Inside an Action.

```php
$user->save();
```

#### [NoDirectDatabaseQueriesInActionsRule](phpstan/Rules/NoDirectDatabaseQueriesInActionsRule.php)

**TL;DR:** Keep filtering, query-builder operations and builder scopes out of Actions. Unfiltered retrieval alone is allowed by this rule.

**Example reported:** Inside an Action.

```php
User::query()->where('name', 'test')->get();
```


`ActionInvokeArgumentsRule` is stricter than the runtime API: enabling it requires adapting the argument-based Action example above to constructor-supplied inputs.

### Data access

#### [OnlyRepositoriesCanQueryDataRule](phpstan/Rules/OnlyRepositoriesCanQueryDataRule.php)

**TL;DR:** Block listed Eloquent read/query methods inside Actions, including unfiltered retrieval. DynamoDB models are exempt.

**Example reported:** Inside an Action.

```php
User::all();
```

#### [OnlyModelManagersCanPersistDataRule](phpstan/Rules/OnlyModelManagersCanPersistDataRule.php)

**TL;DR:** SQL model persistence belongs in classes extending the SQL `BaseModelManager` or `BaseModelService`. DynamoDB models are exempt.

**Example reported:** Inside a controller.

```php
$user->save();
```

#### [WhereRawBindingsRule](phpstan/Rules/WhereRawBindingsRule.php)

**TL;DR:** Dynamic `whereRaw()` and `orWhereRaw()` expressions require a bindings argument; plain string literals are allowed.

**Example reported:** Without bindings.

```php
$query->whereRaw("price > {$column}");
```


`OnlyRepositoriesCanQueryDataRule` is stricter than `NoDirectDatabaseQueriesInActionsRule`: it also reports unfiltered reads such as `User::all()` inside Actions. Despite its name, it does not restrict reads in every other class.

### Dependencies and state

These four rules recognize Actions, Model Services, Repositories and Model Managers by their interfaces or base classes, not by naming alone.

#### [ImmutableDependenciesRule](phpstan/Rules/ImmutableDependenciesRule.php)

**TL;DR:** Object dependencies must be readonly and assigned only in the constructor. DTO and Eloquent model properties are treated as data, not dependencies.

**Example reported:** Without `readonly`.

```php
private PaymentGateway $gateway;
```

#### [NoManualArchitectureServiceInstantiationRule](phpstan/Rules/NoManualArchitectureServiceInstantiationRule.php)

**TL;DR:** Inject architecture-layer collaborators instead of constructing them with `new`. DTOs, models and anonymous classes remain constructible.

**Example reported:**

```php
new UserModelService($manager, $repository)
```

#### [NoMutableStaticStateRule](phpstan/Rules/NoMutableStaticStateRule.php)

**TL;DR:** Architecture classes must not hold static properties shared across instances and requests. Constants and static methods are allowed.

**Example reported:** In a Repository.

```php
private static array $cache = [];
```

#### [NoPublicMutablePropertiesRule](phpstan/Rules/NoPublicMutablePropertiesRule.php)

**TL;DR:** Do not expose mutable public instance properties in architecture classes. Readonly properties and restricted setters are allowed; DTOs and models are outside its scope.

**Example reported:** In a Repository.

```php
public int $defaultLimit = 15;
```


### Naming and validation rules

#### [ModelNamingSuffixRule](phpstan/Rules/ModelNamingSuffixRule.php)

**TL;DR:** Eloquent model class names must end in `Model`.

**Example reported:** Instead of `UserModel`.

```php
class User extends Model
```

#### [ServiceNamingConventionRule](phpstan/Rules/ServiceNamingConventionRule.php)

**TL;DR:** Classes directly extending `BaseModelService` must have names ending in `ModelService`.

**Example reported:**

```php
class UserService extends BaseModelService
```

#### [ValidationRulesMethodNamingRule](phpstan/Rules/ValidationRulesMethodNamingRule.php)

**TL;DR:** `ValidationRules` implementations must have no constructor; their methods must be static and end in `Rules`.

**Example reported:**

```php
public function email(): array
```

**Preferred form:**

```php
public static function emailRules(): array
```

#### [ValidationRulesNoInstantiationRule](phpstan/Rules/ValidationRulesNoInstantiationRule.php)

**TL;DR:** Call static methods on `ValidationRules` implementations instead of creating instances.

**Example reported:**

```php
new UserValidationRules()
```


### Data Validators and jobs

Data Validator rules recognize class names ending in `DataValidator`, optionally limited to configured namespaces.

#### [DataValidatorReturnTypeRule](phpstan/Rules/DataValidatorReturnTypeRule.php)

**TL;DR:** Public Data Validator methods must declare native `bool` or `void` return types. Constructors and non-public helpers are excluded.

**Example reported:** In a Data Validator.

```php
public function validate(): array
```

#### [NoHttpExceptionsInDataValidatorsRule](phpstan/Rules/NoHttpExceptionsInDataValidatorsRule.php)

**TL;DR:** Data Validators must not produce HTTP exceptions or call `abort*()`; use validation or domain exceptions.

**Example reported:** Inside a Data Validator.

```php
abort(422);
```

#### [JobRoutingOutsideJobRule](phpstan/Rules/JobRoutingOutsideJobRule.php)

**TL;DR:** Configure queued-job routing inside the job. By default, `onConnection()`, `onQueue()` and `onGroup()` calls on pending dispatches or external job instances are reported.

**Example reported:** Outside the job.

```php
ImportSubscribersJob::dispatch($url, $listId, $host)->onQueue('imports');
```

## Documentation

| Topic | Guide |
| --- | --- |
| Scaffolding | [Artisan commands, options and custom stubs](docs/code-generation.md) |
| Data access | [Model Managers and bulk writes](docs/model-manager.md) · [Query Builder methods](docs/query-builder-methods.md) · [Soft deletes](docs/soft-delete.md) |
| Cached reads | [Caching, keys and invalidation](docs/repository-caching.md) |
| Input and transformation | [DTOs and validation](docs/validation.md) · [Data Builder pipelines](docs/data-builder.md) |
| DynamoDB | [Models and setup](docs/dynamodb-model.md) |
| Architecture checks | [PHPStan rules](docs/phpstan-rules.md) |
| Transactions | [Atomic writes](examples/Actions/User/CreateUserWithTransaction.php) · [Attribute helper](examples/Actions/User/TransferUserDataAction.php) · [Nested savepoints](examples/Actions/User/CreateUserWithNestedTransaction.php) |
| Expected failures | [Result-based Action](examples/Actions/User/CreateUserWithResult.php) · [Result API](src/Result/Result.php) |

More working patterns are in [`examples/`](examples).

## Development and checks

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

## License

MIT. See [LICENSE](LICENSE).
