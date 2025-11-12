# PHPStan Architecture Rules

This project includes custom PHPStan rules to enforce architectural patterns and best practices.

## Overview

The custom rules ensure that:
- Actions use Repository pattern for data retrieval
- Actions use ModelManager pattern for data persistence
- Direct Eloquent operations are performed only in appropriate layers

## Rules

### 1. NoEloquentStorageMethodsInActionsRule

**Purpose:** Prevents direct Eloquent storage method calls in Action classes.

**Rationale:** Actions should delegate data persistence to ModelManager classes, maintaining separation of concerns and following the repository/manager pattern.

**Violations detected:**
- Calling `save()`, `create()`, `update()`, `delete()`, `forceDelete()` on Eloquent models in Action classes
- Calling `insert()`, `insertOrIgnore()`, `upsert()` on Eloquent models in Action classes
- Calling `updateOrCreate()`, `updateOrInsert()`, `firstOrCreate()`, `firstOrNew()` on Eloquent models in Action classes

**Example violation:**

```php
final readonly class CreateUser implements ArchAction
{
    public function execute(array $data): User
    {
        // ❌ Violation: Direct save() call in Action
        $user = new User($data);
        $user->save();
        
        return $user;
    }
}
```

**Correct approach:**

```php
final readonly class CreateUser implements ArchAction
{
    public function __construct(
        private UserModelService $userModelService
    ) {}
    
    public function execute(array $data): User
    {
        // ✅ Correct: Using ModelManager through Service
        return $this->userModelService->create($data);
    }
}
```

### 2. NoDirectDatabaseQueriesInActionsRule

**Purpose:** Prevents direct database query calls in Action classes.

**Rationale:** Actions should retrieve data through Repository pattern, not by directly querying models. This ensures proper data access layer separation and makes code more testable.

**Violations detected:**
- Query builder methods: `where()`, `whereIn()`, `whereBetween()`, `whereNull()`, etc.
- Retrieval methods: `find()`, `findOrFail()`, `first()`, `firstOrFail()`, `get()`, `all()`
- Aggregate methods: `count()`, `sum()`, `avg()`, `min()`, `max()`
- Relationship methods: `with()`, `withCount()`, `has()`, `whereHas()`
- Other query methods: `orderBy()`, `limit()`, `join()`, `select()`

**Example violation:**

```php
final readonly class GetActiveUsers implements ArchAction
{
    public function execute(): Collection
    {
        // ❌ Violation: Direct query in Action
        return User::where('active', true)
            ->orderBy('name')
            ->get();
    }
}
```

**Correct approach:**

```php
final readonly class GetActiveUsers implements ArchAction
{
    public function __construct(
        private UserModelService $userModelService
    ) {}
    
    public function execute(): Collection
    {
        // ✅ Correct: Using Repository through Service
        return $this->userModelService->findByParams(['active' => true]);
    }
}
```

### 3. OnlyModelManagersCanPersistDataRule

**Purpose:** Ensures data persistence operations are only performed in ModelManager or ModelService classes.

**Rationale:** Centralizes data persistence logic in dedicated ModelManager or ModelService classes, making it easier to maintain, test, and audit database operations.

**Violations detected:**
- Any persistence method (`save()`, `create()`, `update()`, `delete()`, etc.) called outside of classes extending `BaseModelManager` or `BaseModelService`

**Example violation:**

```php
final readonly class SomeRandomClass
{
    public function updateUser(User $user, array $data): void
    {
        // ❌ Violation: Direct save() call outside allowed classes
        $user->fill($data);
        $user->save();
    }
}
```

**Correct approaches:**

```php
// ✅ Correct: Persistence logic in ModelManager
final class UserModelManager extends BaseModelManager
{
    protected function getModelClassName(): string
    {
        return User::class;
    }
    
    public function updateUser(User $user, array $data): bool
    {
        return $this->update($user, $data);
    }
}

// ✅ Also correct: Persistence logic in ModelService
final readonly class UserModelService extends BaseModelService
{
    public function deleteModel(Model $model): bool
    {
        $result = $model->delete();
        return $result !== null && $result;
    }
}
```

## Configuration

The rules are configured in `phpstan.neon`:

```neon
services:
    -
        class: Pekral\Arch\PHPStan\Rules\NoEloquentStorageMethodsInActionsRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\NoDirectDatabaseQueriesInActionsRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\OnlyModelManagersCanPersistDataRule
        tags:
            - phpstan.rules.rule
```

## Exceptions

Some violations are intentionally ignored:

1. **Examples directory** (`examples/*`): Demo code may show both correct and incorrect patterns for educational purposes
2. **BaseModelManager** and **BaseModelService**: These base classes need to call Eloquent methods to implement the patterns

## Running the Rules

```bash
# Run full PHPStan analysis with custom rules
composer analyse

# Or directly with vendor binary
vendor/bin/phpstan analyse --memory-limit=2G
```

## Benefits

These rules provide:

1. **Architectural consistency**: Enforces separation of concerns across the codebase
2. **Maintainability**: Changes to data access logic are isolated to specific layers
3. **Testability**: Actions become easier to test by mocking Services/Repositories instead of database
4. **Code review**: Automatically catches architectural violations during development
5. **Documentation**: Rules serve as living documentation of architectural decisions

## See Also

- [Repository Caching](repository-caching.md)
- [ModelManager Documentation](ModelManager.md)
- [DataBuilder Documentation](DataBuilder.md)

