# Custom Query Builder Methods

## Problem

When using custom query builder methods (e.g., automatically generated `where*` methods) in repository, PHPStorm is unable to correctly detect types and provide autocomplete.

```php
// PHPStorm doesn't know that whereQueue() exists
$this->createQueryBuilder()
    ->whereQueue('crawling')
    ->whereProjectId($project->id)
    ->exists();
```

## Solution

For correct type detection in PHPStorm, it's necessary to use **concrete class name** instead of `static` in PHPDoc annotation on the model.

### ❌ Wrong - using `static`

```php
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static> whereQueue($value)
 */
class Job extends Model
{
    // ...
}
```

PHPStorm cannot correctly evaluate `static` in the context of generic types and autocomplete doesn't work.

### ✅ Correct - using concrete class name

```php
/**
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Job> whereQueue(string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Job> whereProjectId(int $value)
 */
class Job extends Model
{
    // ...
}
```

## Usage in Repository

Thanks to generic types (`@template`) in `BaseRepository` and correctly written PHPDoc annotations on the model, PHPStorm automatically recognizes available methods:

```php
/**
 * @extends \Pekral\Arch\Repository\Mysql\BaseRepository<\App\Models\Job>
 */
final class JobRepository extends BaseRepository
{
    protected function getModelClassName(): string
    {
        return Job::class;
    }
    
    public function hasPendingJobs(Project $project): bool
    {
        // PHPStorm now offers autocomplete for whereQueue() and whereProjectId()
        return $this->createQueryBuilder()
            ->whereQueue('crawling')
            ->whereProjectId($project->id)
            ->exists();
    }
}
```

## Parameter Types

It's recommended to specify parameter types for better type safety:

```php
/**
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Job> whereQueue(string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Job> whereProjectId(int $value)
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Job> whereStatus(string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Job> whereCreatedAt(\DateTimeInterface|string $value)
 */
class Job extends Model
{
    // ...
}
```

## How It Works

1. **BaseRepository** is defined with generic type `@template TModel`
2. **createQueryBuilder()** returns `Builder<TModel>` 
3. Concrete repository (e.g., `JobRepository`) specifies `@extends BaseRepository<Job>`
4. PHPStorm combines this information and knows that `createQueryBuilder()` returns `Builder<Job>`
5. PHPDoc on the `Job` model defines that methods like `whereQueue()` return `Builder<Job>`
6. Result: PHPStorm correctly offers autocomplete for all custom methods

## Practical Example

```php
/**
 * @property int $id
 * @property string $queue
 * @property int $project_id
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Job> whereQueue(string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Job> whereProjectId(int $value)
 * @method static \Illuminate\Database\Eloquent\Builder<\App\Models\Job> whereStatus(string $value)
 */
final class Job extends Model
{
    protected $fillable = [
        'queue',
        'project_id',
        'status',
    ];
}
```

## Notes

- Always use **fully qualified class name** (including namespace)
- Specify **parameter types** for better type safety
- PHPDoc annotations must be **above class definition**, not in class body
- Use `@method static` for calls on class (`Job::whereQueue()`)
- Builder always returns `Builder<ConcreteModel>`, not `Builder<static>`

