# ModelManager Documentation

The ModelManager provides a clean interface for database write operations (create, update, delete) with support for batch processing and duplicate handling.

## Overview

The ModelManager is designed to handle all write operations for your models while providing consistent interfaces and error handling. It supports both single record operations and bulk operations for better performance.

## BaseModelManager

The `BaseModelManager` abstract class provides the foundation for all model managers. It includes:

- Single record creation
- Bulk operations (create, update, insert or ignore)
- Parameter-based deletion
- Automatic timestamp handling

## Available Methods

### Single Record Operations

#### `create(array $data): Model`

Creates a single record and returns the model instance.

```php
$user = $userModelManager->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password123',
]);
```

### Bulk Operations

#### `bulkCreate(array $dataArray): int`

Creates multiple records in a single database operation. Returns the number of records processed.

```php
$users = [
    ['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'password1'],
    ['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'password2'],
    ['name' => 'Charlie', 'email' => 'charlie@example.com', 'password' => 'password3'],
];

$createdCount = $userModelManager->bulkCreate($users);
// Returns: 3
```

#### `insertOrIgnore(array $dataArray): int`

Bulk inserts records while ignoring duplicates based on unique constraints. This is particularly useful for data imports where you want to avoid duplicate key errors.

```php
$users = [
    ['name' => 'Alice Updated', 'email' => 'alice@example.com', 'password' => 'newpassword'], // Duplicate email
    ['name' => 'David', 'email' => 'david@example.com', 'password' => 'password4'], // New user
];

$processedCount = $userModelManager->insertOrIgnore($users);
// Returns: 2 (number of records processed, including ignored duplicates)
```

**Important Notes:**
- The method returns the number of records in the input array, not the actual number of inserted records
- Duplicate records are silently ignored based on unique constraints
- Timestamps (`created_at`, `updated_at`) are automatically added if not provided

#### `bulkUpdate(array $dataArray, string $keyColumn = 'id'): int`

Updates multiple records based on a key column. Returns the number of updated records.

```php
$updates = [
    ['id' => 1, 'name' => 'Alice Updated'],
    ['id' => 2, 'name' => 'Bob Updated'],
    ['id' => 3, 'name' => 'Charlie Updated'],
];

$updatedCount = $userModelManager->bulkUpdate($updates);
// Returns: 3
```

### Deletion Operations

#### `deleteByParams(array $parameters): bool`

Deletes records matching the given parameters.

```php
$deleted = $userModelManager->deleteByParams([
    'status' => 'inactive',
    'created_at' => '< 2023-01-01',
]);
// Returns: true if any records were deleted
```

## Implementation Example

```php
<?php

namespace App\Services;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use App\Models\User;

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

## Usage in Actions

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Tests\Models\User;

final readonly class BulkImportUsers
{
    public function __construct(
        private UserModelManager $userModelManager,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $userData
     * @return array{
     *     total_processed: int,
     *     created: int,
     *     ignored: int
     * }
     */
    public function execute(array $userData): array
    {
        if ($userData === []) {
            return [
                'total_processed' => 0,
                'created' => 0,
                'ignored' => 0,
            ];
        }

        // Prepare data with timestamps
        $preparedData = $this->prepareUserData($userData);

        // Count existing users before import
        $existingCount = User::count();

        // Use insertOrIgnore to handle duplicates
        $processedCount = $this->userModelManager->insertOrIgnore($preparedData);

        // Count users after import
        $newCount = User::count();
        $createdCount = $newCount - $existingCount;
        $ignoredCount = $processedCount - $createdCount;

        return [
            'total_processed' => $processedCount,
            'created' => $createdCount,
            'ignored' => $ignoredCount,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $userData
     * @return array<int, array<string, mixed>>
     */
    private function prepareUserData(array $userData): array
    {
        $now = now();

        return array_map(function (array $data) use ($now): array {
            return array_merge($data, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $userData);
    }
}
```

## Performance Considerations

### Bulk Operations vs Single Operations

- **Use bulk operations** when processing multiple records
- **Use single operations** for individual record creation with complex business logic
- Bulk operations are significantly faster for large datasets

### insertOrIgnore vs bulkCreate

- **Use `insertOrIgnore`** when you expect potential duplicates and want to avoid errors
- **Use `bulkCreate`** when you're certain there are no duplicates
- `insertOrIgnore` automatically handles unique constraint violations

### Memory Usage

- Bulk operations load all data into memory at once
- For very large datasets, consider processing in chunks
- Monitor memory usage when processing large imports

## Error Handling

The ModelManager methods handle common database errors gracefully:

- **Duplicate key errors**: Handled by `insertOrIgnore`
- **Validation errors**: Should be handled at the Action level using DataValidator
- **Database connection errors**: Propagated as exceptions

## Best Practices

1. **Always validate data** before using bulk operations
2. **Use appropriate bulk methods** based on your use case
3. **Handle timestamps consistently** - they're automatically added by `insertOrIgnore`
4. **Monitor performance** for large bulk operations
5. **Use transactions** when combining multiple operations
6. **Test thoroughly** with duplicate data scenarios

## Testing

The ModelManager includes comprehensive tests covering:

- Empty data handling
- Single record operations
- Bulk operations with various data sets
- Duplicate handling scenarios
- Error conditions

Example test:

```php
public function testInsertOrIgnoreWithDuplicateData(): void
{
    // Arrange
    User::factory()->create(['email' => 'existing@example.com']);
    $data = [
        ['name' => 'New User', 'email' => 'existing@example.com', 'password' => 'password123'],
        ['name' => 'Another User', 'email' => 'new@example.com', 'password' => 'password456'],
    ];
    
    // Act
    $result = $this->userModelManager->insertOrIgnore($data);
    
    // Assert
    $this->assertSame(2, $result);
    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseHas('users', ['name' => 'Another User', 'email' => 'new@example.com']);
}
```
