# Soft Delete Support

The package provides complete support for soft delete operations.

## Basic Usage

### Soft Delete by ID

```php
// Soft delete user by ID
$deleted = $userService->softDelete($user->id);

if ($deleted) {
    echo "User was successfully deleted.";
}
```

### Soft Delete by Parameters

```php
// Soft delete all users with specific email
$deletedCount = $userService->softDeleteByParams([
    'email' => 'old@example.com'
]);

echo "Deleted {$deletedCount} users.";

// Soft delete multiple users at once
$deletedCount = $userService->softDeleteByParams([
    'email' => ['user1@example.com', 'user2@example.com']
]);
```

### Restore Soft Deleted Records

```php
// Restore by ID
$restored = $userService->restore($user->id);

if ($restored) {
    echo "User was successfully restored.";
}

// Restore by parameters
$restoredCount = $userService->restoreByParams([
    'email' => 'old@example.com'
]);
```

### Permanent Delete

```php
// Permanent delete by ID
$deleted = $userService->forceDelete($user->id);

// Permanent delete by parameters
$deletedCount = $userService->forceDeleteByParams([
    'email' => 'old@example.com'
]);
```

## Advanced Features

### Combination with Regular Operations

```php
// Soft delete and then restore
$userService->softDelete($user->id);
$userService->restore($user->id);

// Check if record is soft deleted
$user = User::withTrashed()->find($user->id);
if ($user->trashed()) {
    echo "User is soft deleted.";
}
```

### Batch Operations

```php
// Soft delete all inactive users
$deletedCount = $userService->softDeleteByParams([
    'active' => false,
    'last_login' => '<', now()->subMonths(6)
]);

// Restore all users from specific period
$restoredCount = $userService->restoreByParams([
    'deleted_at' => '>=', now()->subDays(30)
]);
```

### Usage with Collection

```php
use Illuminate\Support\Collection;

$emails = new Collection(['user1@example.com', 'user2@example.com']);

$deletedCount = $userService->softDeleteByParams([
    'email' => $emails->toArray()
]);
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
    $deleted = $userService->softDelete($nonExistentId);
    
    if (!$deleted) {
        echo "User not found or already deleted.";
    }
} catch (\Exception $e) {
    echo "Error during soft delete: " . $e->getMessage();
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
    $userService->softDeleteByParams([
        'id' => $chunk->toArray()
    ]);
}
```

## Monitoring and Logging

```php
// Logging soft delete operations
$deletedCount = $userService->softDeleteByParams([
    'role' => 'temporary'
]);

Log::info("Soft deleted {$deletedCount} temporary users");
```
