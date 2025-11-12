# PHPStan Architecture Rules

This project includes custom PHPStan rules to enforce architectural patterns and best practices.

## Overview

The custom rules enforce strict architectural separation to maintain clean, testable, and maintainable code.

### Architecture Layers & Responsibilities

```
┌─────────────────────────────────────────────────────────┐
│                       ACTION LAYER                       │
│  • Business logic orchestration                         │
│  • Simple retrieval without conditions (User::get())   │
│  • Delegates to Service layer                           │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                      SERVICE LAYER                       │
│  • Coordinates Repository and ModelManager              │
│  • Transaction handling                                  │
│  • Complex business operations                           │
└─────────────────────────────────────────────────────────┘
            ↓                                    ↓
┌──────────────────────────┐      ┌──────────────────────────┐
│    REPOSITORY LAYER      │      │   MODELMANAGER LAYER     │
│  • Data RETRIEVAL        │      │  • Data PERSISTENCE      │
│  • SELECT with WHERE     │      │  • INSERT operations     │
│  • Query conditions      │      │  • UPDATE operations     │
│  • Eloquent scopes       │      │  • DELETE operations     │
│  • Aggregates with WHERE │      │  • Bulk operations       │
└──────────────────────────┘      └──────────────────────────┘
            ↓                                    ↓
            └──────────────┬─────────────────────┘
                          ↓
                   ┌──────────────┐
                   │   DATABASE   │
                   └──────────────┘
```

**Key Principles:**
- **Repository** → Data retrieval with conditions (SELECT queries with WHERE)
- **ModelManager** → Data persistence (INSERT, UPDATE, DELETE operations)
- **Actions** → Business logic coordination (NO direct database queries with conditions)

This ensures:
- Clear separation of concerns
- Improved testability
- Consistent data access patterns
- Easy to maintain and refactor

## Rules

### 1. NoEloquentStorageMethodsInActionsRule

**Purpose:** Prevents direct Eloquent storage method calls in Action classes.

**Rationale:** Actions should delegate data persistence to ModelManager classes. All INSERT, UPDATE, and DELETE operations must be encapsulated in ModelManager classes to maintain separation of concerns and ensure consistent data persistence patterns.

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
        // ✅ Correct: Data persistence through ModelManager
        // Service delegates to ModelManager internally
        return $this->userModelService->create($data);
    }
}
```

**Architecture flow:**
```
Action → Service → ModelManager → Database (INSERT/UPDATE/DELETE)
```

### 2. NoDirectDatabaseQueriesInActionsRule

**Purpose:** Prevents SQL queries with conditions (WHERE clauses or Eloquent scopes) in Action classes.

**Rationale:** Actions must delegate data operations to appropriate layers:
- **Repository classes** - for data retrieval with conditions (SELECT queries with WHERE)
- **ModelManager classes** - for data persistence (INSERT, UPDATE, DELETE operations)

Simple retrieval methods (`get()`, `all()`, `count()`, etc.) are allowed WITHOUT conditions. Any query with WHERE clauses or scopes must be encapsulated in Repository classes.

**Rule logic:**
1. **Allowed without conditions:** `get()`, `all()`, `first()`, `find()`, `count()`, `sum()`, `avg()`, `min()`, `max()`, `exists()`, `pluck()`
2. **Always forbidden:** `where()`, `whereIn()`, `orderBy()`, `limit()`, `join()`, `select()`, `with()`, `has()`, and all other query builder methods
3. **Eloquent scopes forbidden:** Any custom scope (e.g., `->active()`, `->published()`) cannot be used in Actions

**Violations detected:**

**Type 1: Direct query builder methods**
```php
// ❌ where(), whereIn(), orderBy(), etc. are always forbidden
User::where('active', true)->get();
User::whereIn('status', ['active', 'pending'])->count();
User::orderBy('created_at')->get();
```

**Type 2: Retrieval methods after conditions**
```php
// ❌ get(), count(), sum() etc. after WHERE or scopes
User::where('verified', true)->count();
User::active()->sum('points');  // active() is a scope
```

**Type 3: Eloquent scopes**
```php
// ❌ Custom scopes cannot be called in Actions
User::active()->get();
User::published()->count();
User::verified()->first();
```

**Allowed patterns:**

```php
final readonly class GetAllUsers implements ArchAction
{
    public function execute(): Collection
    {
        // ✅ Simple get() without conditions is allowed
        return User::get();
    }
}
```

```php
final readonly class CountAllUsers implements ArchAction
{
    public function execute(): int
    {
        // ✅ Simple count() without conditions is allowed
        return User::count();
    }
}
```

```php
final readonly class GetUserById implements ArchAction
{
    public function execute(int $id): ?User
    {
        // ✅ Simple find() is allowed
        return User::find($id);
    }
}
```

**Correct approach for queries with conditions:**

```php
final readonly class GetActiveUsers implements ArchAction
{
    public function __construct(
        private UserModelService $userModelService
    ) {}
    
    public function execute(): Collection
    {
        // ✅ Correct: Data retrieval with conditions through Repository
        // Service delegates to Repository internally
        return $this->userModelService->findByParams(['active' => true]);
    }
}
```

```php
final readonly class CountVerifiedUsers implements ArchAction
{
    public function __construct(
        private UserModelService $userModelService
    ) {}
    
    public function execute(): int
    {
        // ✅ Correct: Aggregates with conditions through Repository
        // Service delegates to Repository internally
        return $this->userModelService->countByParams(['verified' => true]);
    }
}
```

**Architecture layers:**
```
Action → Service → Repository (for data retrieval with conditions)
Action → Service → ModelManager (for data persistence)
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
- [Model Manager](model-manager.md)
- [Data Builder](data-builder.md)

