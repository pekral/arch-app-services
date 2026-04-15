# Upgrade Guide

## Upgrading to Laravel 13

### Requirements

- PHP 8.4+ (unchanged, already required by this package)
- Laravel 13.x (`illuminate/*: ^13.0` already declared in `composer.json`)
- `orchestra/testbench: ^11.0` (already declared in `composer.json`)

### Cache Serialization (`serializable_classes`)

Laravel 13 introduces a `serializable_classes` option in `config/cache.php` that defaults to `false` for **new applications**. When set to `false`, PHP's `unserialize()` refuses to reconstruct objects from cache.

If you use `CacheableRepository` (repository caching), cached results contain Eloquent model instances and collections. With `serializable_classes => false`, cache deserialization will fail.

**Option A** - Disable the restriction:

```php
// config/cache.php
'stores' => [
    'your-store' => [
        // ...
        'serializable_classes' => true,
    ],
],
```

**Option B** - Allowlist specific classes:

```php
// config/cache.php
'stores' => [
    'your-store' => [
        // ...
        'serializable_classes' => [
            \Illuminate\Database\Eloquent\Collection::class,
            \Illuminate\Pagination\LengthAwarePaginator::class,
            \App\Models\User::class, // your model classes
            \Carbon\Carbon::class,
        ],
    ],
],
```

> **Note:** If you are upgrading an existing application from Laravel 12, your `config/cache.php` is unchanged and this does not apply until you add the option manually.

### Pagination Default

Laravel 13 changed the framework's default pagination from 15 to 25 items per page. This package controls its own default via `config('arch.default_items_per_page', 15)` and is **not affected** by this change.

### Cache Key Generation

Cache key hashing was changed from `serialize()` to `json_encode()`. This means:

- Existing cached entries will **not** be found after upgrading (cache miss, not an error)
- New entries will use the updated key format
- If you need to avoid cache misses, clear your repository cache before deploying

### Breaking Changes Checklist

| Area | Impact | Action Required |
|------|--------|-----------------|
| `serializable_classes` | Medium | Configure cache allowlist if using `CacheableRepository` on new L13 apps |
| Cache key format | Low | Clear repository cache after upgrade (optional, avoids stale entries) |
| Pagination default | None | Package uses its own default (15) |
| Pipeline API | None | No changes |
| Validation API | None | No changes |
| Console commands | None | `GeneratorCommand` API unchanged |
