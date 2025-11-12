# PHPStan Custom Rules

This directory contains custom PHPStan rules that enforce architectural patterns for the Arch App Services package.

## Rules

### 1. NoEloquentStorageMethodsInActionsRule

Prevents direct Eloquent storage method calls in Action classes.

**Blocked methods:**
- `save()`
- `create()`
- `update()`
- `delete()`
- `forceDelete()`
- `insert()`
- `insertOrIgnore()`
- `upsert()`
- `updateOrCreate()`
- `updateOrInsert()`
- `firstOrCreate()`
- `firstOrNew()`

**Rationale:** Actions should delegate data persistence to ModelManager classes.

### 2. NoDirectDatabaseQueriesInActionsRule

Prevents SQL queries with conditions (WHERE clauses or Eloquent scopes) in Action classes.

**Architecture Responsibility:**
- **Repository** → Data retrieval with conditions (SELECT with WHERE)
- **ModelManager** → Data persistence (INSERT, UPDATE, DELETE)
- **Actions** → Simple retrieval without conditions is allowed

**Rule Logic:**
- **Allowed without conditions:** `get()`, `all()`, `first()`, `find()`, `count()`, `sum()`, `avg()`, `min()`, `max()`, `exists()`, `pluck()`
- **Always blocked:** `where()`, `whereIn()`, `orderBy()`, `limit()`, `join()`, `select()`, `with()`, `has()`, etc.
- **Eloquent scopes blocked:** Custom query scopes like `->active()`, `->published()`, etc.

**Examples:**
```php
// ✅ Allowed - simple retrieval without conditions
User::get();
User::count();
User::find($id);

// ❌ Blocked - must be in Repository
User::where('active', true)->get();
User::orderBy('name')->count();

// ❌ Blocked - scopes must be in Repository
User::active()->get();
User::published()->count();
```

**Rationale:** Data retrieval with conditions must be encapsulated in Repository classes for consistent data access patterns.

### 3. OnlyModelManagersCanPersistDataRule

Ensures data persistence operations are only performed in ModelManager or ModelService classes.

**Blocked outside ModelManager/ModelService:**
- All persistence methods: `save()`, `create()`, `update()`, `delete()`, etc.

**Rationale:** Centralizes data persistence logic in dedicated ModelManager or ModelService classes.

## Usage

These rules are automatically loaded by PHPStan through the configuration in `phpstan.neon`.

## Testing

To test the rules, create a file that violates them and run PHPStan:

```bash
vendor/bin/phpstan analyse path/to/test-file.php
```

## Documentation

For detailed documentation, see [docs/phpstan-rules.md](../docs/phpstan-rules.md).

