# DynamoDB Model

Arch App Services includes optional DynamoDB Repositories and Model Managers backed by `baopham/dynamodb`, which provides an Eloquent-like interface. The driver is not installed automatically with Arch App Services.

## Installation and Configuration

### 1. Package Installation

Applications using the DynamoDB integration must install the driver explicitly:

```bash
composer require "baopham/dynamodb:^7.1"
```

The Arch App Services repository keeps this driver in `require-dev` so its DynamoDB integration tests still run. Applications using only SQL Repositories and Model Managers do not need it.

### 2. Configuration

In `TestCase.php`, the configuration for local DynamoDB is set:

```php
$app['config']->set('dynamodb.default', 'test');
$app['config']->set('dynamodb.connections.test', [
    'credentials' => [
        'key' => 'fakeMyKeyId',
        'secret' => 'fakeSecretAccessKey',
    ],
    'region' => 'us-east-1',
    'endpoint' => 'http://localhost:8021',
    'debug' => false,
]);
```

### 3. DynamoDB Service Provider

The provider must be registered in `getPackageProviders()`:

```php
protected function getPackageProviders(mixed $app): array
{
    return [
        ArchServiceProvider::class,
        \BaoPham\DynamoDb\DynamoDbServiceProvider::class,
    ];
}
```

## Creating a DynamoDB Model

The model must extend `BaoPham\DynamoDb\DynamoDbModel`:

```php
<?php

namespace Pekral\Arch\Tests\Models;

use BaoPham\DynamoDb\DynamoDbModel;

final class UserDynamoDb extends DynamoDbModel
{
    protected $connection = 'test';
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dynamoDbIndexKeys = [
        'email-index' => [
            'hash' => 'email',
        ],
    ];
}
```

## Key Properties

### Primary Key
- **Must be string type** - DynamoDB does not support auto-increment
- Use UUID: `$user->id = Uuid::uuid4()->toString();`
- Set `public $incrementing = false;`

### Connection
- Specify connection: `protected $connection = 'test';`
- Corresponds to the configuration key in `config/dynamodb.php`

### Indexes
- Define using `$dynamoDbIndexKeys`
- Must match the definition in the table JSON file

## Table Definition

The table is defined in `tests/database/dynamodb-tables/users.json`:

```json
{
  "TableName": "users",
  "KeySchema": [
    {
      "AttributeName": "id",
      "KeyType": "HASH"
    }
  ],
  "AttributeDefinitions": [
    {
      "AttributeName": "id",
      "AttributeType": "S"
    },
    {
      "AttributeName": "email",
      "AttributeType": "S"
    }
  ],
  "GlobalSecondaryIndexes": [
    {
      "IndexName": "email-index",
      "KeySchema": [
        {
          "AttributeName": "email",
          "KeyType": "HASH"
        }
      ],
      "Projection": {
        "ProjectionType": "ALL"
      }
    }
  ]
}
```

## Usage

### Creating a Record

```php
$user = new UserDynamoDb();
$user->id = Uuid::uuid4()->toString();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->password = 'hashed_password';
$user->save();
```

### Finding by ID

```php
$user = UserDynamoDb::find($userId);
```

### Finding by Index

```php
$user = UserDynamoDb::where('email', 'john@example.com')->first();
```

### Updating

```php
$user = UserDynamoDb::find($userId);
$user->name = 'Updated Name';
$user->save();
```

### Deleting

```php
$user = UserDynamoDb::find($userId);
$user->delete();
```

## Local DynamoDB Environment

### Starting DynamoDB Local

```bash
docker-compose up -d dynamodb-local
```

### Checking Connection

```bash
bash scripts/check-dynamodb.sh
```

### Port and Endpoint

- Port: `8021`
- Endpoint: `http://localhost:8021`

## Differences from Eloquent

### Not Supported:
- Auto-increment ID
- Relationships
- Soft deletes (not natively supported)
- Some complex query builders

### Supported:
- `find()`, `where()`, `first()`, `all()`
- `create()`, `update()`, `delete()`
- `save()`, `saveAsync()`
- Timestamps
- Query scopes
- Index queries

## Testing

Running tests:

```bash
vendor/bin/pest tests/Unit/Models/UserDynamoDbTest.php
```

Tests cover:
- ✅ Creating a new record
- ✅ Finding a record by ID
- ✅ Finding a record using an index
- ✅ Updating a record
- ✅ Deleting a record

## References

- [baopham/dynamodb GitHub](https://github.com/baopham/laravel-dynamodb)
- [AWS DynamoDB Documentation](https://aws.amazon.com/dynamodb/)
- [DynamoDB Local Documentation](https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/DynamoDBLocal.html)
