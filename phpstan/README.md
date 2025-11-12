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

Prevents direct database query calls in Action classes.

**Blocked methods:**
- Query builders: `where()`, `whereIn()`, `whereBetween()`, `whereNull()`, etc.
- Retrieval: `find()`, `findOrFail()`, `first()`, `firstOrFail()`, `get()`, `all()`
- Aggregates: `count()`, `sum()`, `avg()`, `min()`, `max()`
- Relationships: `with()`, `withCount()`, `has()`, `whereHas()`
- Other: `orderBy()`, `limit()`, `join()`, `select()`

**Rationale:** Actions should retrieve data through Repository pattern.

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

