# Soft Delete Support

Laravel's `SoftDeletes` trait works seamlessly with the abstractions provided by this package. Services and model managers always delegate to native Eloquent behaviour, so soft delete support depends on your model setup.

## Key integrations

- `BaseModelService::deleteModel()` calls `$model->delete()`. When the model uses `SoftDeletes`, the record is moved to the recycle bin instead of being removed permanently.
- `BaseModelManager::deleteByParams()` issues a `where(...)->delete()` query. The return value is `true` when any record was affected. With `SoftDeletes` enabled the records are flagged as deleted.
- Repositories expose the underlying query builder, allowing you to chain `withTrashed()` or `onlyTrashed()` just like in plain Laravel.

## Example workflow

```php
$user = $userService->getOneByParams(['email' => 'arch@example.com']);

// Soft delete via service (delegates to model->delete()).
$userService->deleteModel($user);

// Query soft deleted records using the repository.
$trashedUser = $userRepository->query()
    ->withTrashed()
    ->where('email', 'arch@example.com')
    ->first();

// Permanently remove the record if needed.
$trashedUser->forceDelete();
```

## Model setup

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class User extends Model
{
    use SoftDeletes;
}
```

Add the `deleted_at` column in the relevant migration:

```php
Schema::table('users', function (Blueprint $table): void {
    $table->softDeletes();
});
```

## Restoring records

Soft deleted models can be restored either directly or through queries:

```php
User::withTrashed()->find($userId)?->restore();

User::onlyTrashed()
    ->where('deleted_at', '>=', now()->subDays(30))
    ->restore();
```

## Batch deletions

When deleting in bulk, rely on the model manager so your write logic stays in one place:

```php
$deleted = $userModelManager->deleteByParams([
    'status' => 'inactive',
]);

if ($deleted) {
    // At least one row was soft deleted.
}
```

Because the package delegates to Eloquent, all existing Laravel tooling (events, observers, scopes) continues to work exactly as it does in a standard application.
