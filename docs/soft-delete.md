# Soft Delete Support

The package provides support for soft delete operations through Laravel's built-in SoftDeletes trait. All operations work seamlessly with soft deleted models.

## Basic Usage

### Soft Delete Models

```php
// Soft delete a model instance
$user = User::find(1);
$user->delete(); // This will soft delete if SoftDeletes trait is used

// Soft delete by parameters using Model Manager
$deletedCount = $userModelManager->deleteByParams([
    'email' => 'old@example.com'
]);

echo "Deleted {$deletedCount} users.";
```

### Restore Soft Deleted Records

```php
// Restore a soft deleted model
$user = User::withTrashed()->find(1);
$user->restore();

// Restore multiple records
User::withTrashed()
    ->where('email', 'old@example.com')
    ->restore();
```

### Permanent Delete

```php
// Force delete (permanent delete)
$user = User::withTrashed()->find(1);
$user->forceDelete();

// Force delete multiple records
User::withTrashed()
    ->where('email', 'old@example.com')
    ->forceDelete();
```

## Advanced Features

### Using with Repository and Service

```php
// Using Repository to find soft deleted records
$user = $userRepository->query()
    ->withTrashed()
    ->where('email', 'old@example.com')
    ->first();

// Using Service to delete models
$user = $userService->getOneByParams(['email' => 'user@example.com']);
$userService->deleteModel($user); // This will soft delete

// Check if record is soft deleted
if ($user->trashed()) {
    echo "User is soft deleted.";
}
```

### Batch Operations

```php
// Soft delete all inactive users
$deletedCount = $userModelManager->deleteByParams([
    'active' => false,
    'last_login' => '<', now()->subMonths(6)
]);

// Restore all users from specific period
User::withTrashed()
    ->where('deleted_at', '>=', now()->subDays(30))
    ->restore();
```

### Using with Actions

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class DeleteUser
{
    public function __construct(private UserModelService $userModelService)
    {
    }

    public function handle(User $user): bool
    {
        return $this->userModelService->deleteModel($user);
    }
}
```

## Model Requirements

To use soft delete, your model must implement the `SoftDeletes` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    
    // ... rest of model
}
```

## Migration Requirements

Make sure you have the `deleted_at` column in your migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
```

## Error Handling

```php
try {
    $user = User::findOrFail($userId);
    $deleted = $userService->deleteModel($user);
    
    if (!$deleted) {
        echo "User could not be deleted.";
    }
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    echo "User not found.";
} catch (\Exception $e) {
    echo "Error during delete: " . $e->getMessage();
}
```

## Performance Tips

### Using Indexes

```php
// Add index on deleted_at column for better performance
Schema::table('users', function (Blueprint $table) {
    $table->index('deleted_at');
});
```

### Batch Operations

```php
// For large amounts of records, use batch operations
$userIds = User::where('active', false)->pluck('id');

foreach ($userIds->chunk(1000) as $chunk) {
    $userModelManager->deleteByParams([
        'id' => $chunk->toArray()
    ]);
}
```

## Monitoring and Logging

```php
// Logging soft delete operations
$deletedCount = $userModelManager->deleteByParams([
    'role' => 'temporary'
]);

Log::info("Soft deleted {$deletedCount} temporary users");
```
