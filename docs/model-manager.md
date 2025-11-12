# Model Manager

`Pekral\Arch\ModelManager\Mysql\BaseModelManager` centralises write operations (create, update, delete) for Eloquent models. It is intended to be extended inside your application services while repositories remain responsible for read queries.

## Extending the base class

```php
<?php

namespace App\ModelManagers;

use App\Models\User;
use Pekral\Arch\ModelManager\Mysql\BaseModelManager;

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

Every concrete manager must return the associated model class via `getModelClassName()`. The base class takes care of instantiating the model and running the relevant Eloquent operations.

## Method reference

### `create(array $data): Model`

Creates a single record using `fill()` + `save()` and returns the newly persisted model instance.

### `update(Model $model, array $data): bool`

Applies attributes to an existing model and calls `save()`. Returns the boolean result of the save operation.

### `deleteByParams(array $parameters): bool`

Runs a `where($parameters)->delete()` query on the underlying model. Returns `true` when any record was deleted.

### `bulkCreate(array $dataArray): int`

Calls `Model::insert()` with the provided array. When the payload is empty `0` is returned. Otherwise the method returns the number of rows passed in (the return value from Eloquent is cast to a count).

### `insertOrIgnore(array $dataArray): void`

Delegates to `Model::insertOrIgnore()`. The method performs no work for an empty payload. There is no return value; duplicates are silently skipped by the database.

### `bulkUpdate(array $dataArray, string $keyColumn = 'id'): int`

Performs a loop of `UPDATE` statements. Each row must contain the key column (default `id`). The method removes the key from the payload, skips entries without data, and returns the total number of updated rows.

### `rawMassUpdate(array $values, array|string|null $uniqueBy = null): int`

Uses the `iksaku/laravel-mass-update` package (and its `MassUpdatable` trait) to run a single optimised SQL update. When the package or trait are missing a `MassUpdateNotAvailable` exception is thrown. Accepts arrays or model instances and returns the number of affected rows.

### `updateOrCreate(array $attributes, array $values = []): Model`

Pass-through to `Model::updateOrCreate()`.

### `getOrCreate(array $attributes, array $values = []): Model`

Pass-through to `Model::firstOrCreate()`.

### `createNewModelInstance(): Model`

Helper returning a new model instance with an empty attribute array. Primarily useful when you need a non-persisted model with the correct fillable definitions.

## Usage with services

`Pekral\Arch\Service\BaseModelService` expects a model manager for write operations. Typical service wiring looks like:

```php
final class UserModelService extends BaseModelService
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserModelManager $modelManager,
    ) {
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getModelManager(): BaseModelManager
    {
        return $this->modelManager;
    }

    protected function getModelClass(): string
    {
        return User::class;
    }
}
```

Read operations (`findOneByParams`, `paginateByParams`, …) are executed through the repository, while write operations (`create`, `bulkUpdate`, `deleteByParams`, …) use the model manager internally.

## When to choose each method

- Use `create()` and `update()` for single-record flows where you need lifecycle events (`creating`, `saved`, …).
- Use `bulkCreate()` when inserting large datasets without model events.
- Use `insertOrIgnore()` to avoid duplicate key errors (no information about skipped rows is returned).
- Use `bulkUpdate()` for batched updates when model events are not required but dataset size is moderate.
- Use `rawMassUpdate()` for very large updates where a single SQL statement is preferable and the model can adopt the `MassUpdatable` trait.
- Use `deleteByParams()` for soft deletes or hard deletes through query builders.

## Related files

- [`src/ModelManager/Mysql/BaseModelManager.php`](../src/ModelManager/Mysql/BaseModelManager.php)
- [`src/Service/BaseModelService.php`](../src/Service/BaseModelService.php)
- [`examples/Services/User/UserModelManager.php`](../examples/Services/User/UserModelManager.php)
