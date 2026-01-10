<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;
use Pekral\Arch\Examples\Services\User\UserDynamoModelManager;
use Pekral\Arch\Examples\Services\User\UserDynamoModelService;
use Pekral\Arch\Examples\Services\User\UserDynamoRepository;
use Pekral\Arch\Exceptions\DynamoDbNotSupported;
use Pekral\Arch\Tests\Models\UserDynamoModel;
use ReflectionClass;

use function app;
use function expect;
use function fake;
use function test;

test('test basic CRUD operations', function (): void {
    $userModelService = app(UserDynamoModelService::class);
    $modelId = fake()->uuid();
    $data = [
        'id' => $modelId,
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ];
    /** @var \Pekral\Arch\Tests\Models\UserDynamoModel $user */
    $user = $userModelService->create($data);

    expect($user)->toBeInstanceOf(UserDynamoModel::class)
        ->and($user->id)->toBe($modelId)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');

    $userModelService->updateModel($user, ['password' => 'newpassword']);
    /** @var \Pekral\Arch\Tests\Models\UserDynamoModel|null $user */
    $user = $userModelService->findOneByParams(['id' => $modelId]);

    expect($user)->not->toBeNull();
    assert($user !== null);
    expect($user->password)->toBe('newpassword');
    unset($user);

    /** @var \Pekral\Arch\Tests\Models\UserDynamoModel $user */
    $user = $userModelService->getOneByParams(['id' => $modelId]);
    expect($user->password)->toBe('newpassword');

    $userModelService->deleteModel($user);
    expect($userModelService->findOneByParams(['id' => $modelId]))->toBeNull();
});

test('paginate by params returns paginated results', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 20; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    $result = $userModelService->paginateByParams();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(15);
});

test('paginate by params with filters', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 5; $i++) {
        $userModelService->create([
            'email' => sprintf('john%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'John',
            'password' => 'password123',
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        $userModelService->create([
            'email' => sprintf('jane%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'Jane',
            'password' => 'password123',
        ]);
    }

    $result = $userModelService->paginateByParams(['name' => 'John']);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(5);
});

test('paginate by params with custom per page', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 20; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    $result = $userModelService->paginateByParams([], [], 10);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(10);
});

test('paginate by params with order by', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'alice@example.com',
        'name' => 'Alice',
        'password' => 'password123',
    ]);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'bob@example.com',
        'name' => 'Bob',
        'password' => 'password123',
    ]);

    $result = $userModelService->paginateByParams([], [], null, ['name' => 'desc']);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(2);

    $firstUser = $result->items()[0];
    expect($firstUser)->toBeInstanceOf(UserDynamoModel::class);
    assert($firstUser instanceof UserDynamoModel);
    expect($firstUser->name)->toBe('Bob');
});

test('paginate by params with with relations throws exception', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    $userModelService->paginateByParams([], ['someRelation']);
})->throws(DynamoDbNotSupported::class, 'eager loading');

test('paginate by params with group by throws exception', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    $userModelService->paginateByParams([], [], null, [], ['name']);
})->throws(DynamoDbNotSupported::class, 'GROUP BY');

test('count by params with group by throws exception', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    $userModelService->countByParams([], ['name']);
})->throws(DynamoDbNotSupported::class, 'GROUP BY');

test('get one by params with with relations throws exception', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    $userModelService->getOneByParams(['email' => 'test@example.com'], ['someRelation']);
})->throws(DynamoDbNotSupported::class, 'eager loading');

test('find one by params with with relations throws exception', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    $userModelService->findOneByParams(['email' => 'test@example.com'], ['someRelation']);
})->throws(DynamoDbNotSupported::class, 'eager loading');

test('count by params without filters', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 5; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    $count = $userModelService->countByParams([]);

    expect($count)->toBe(5);
});

test('count by params with filters', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 3; $i++) {
        $userModelService->create([
            'email' => sprintf('john%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'John',
            'password' => 'password123',
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        $userModelService->create([
            'email' => sprintf('jane%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'Jane',
            'password' => 'password123',
        ]);
    }

    /** @var array<string, string> $params */
    $params = ['name' => 'John'];
    $count = $userModelService->countByParams($params);

    expect($count)->toBe(3);
});

test('paginate by params with empty params', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 5; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    $result = $userModelService->paginateByParams([]);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(5)
        ->and($result->total())->toBe(5);
});

test('paginate by params with empty order by', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 3; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    $result = $userModelService->paginateByParams([], [], null, []);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(3);
});

test('paginate by params uses default items per page from config', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 20; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    $result = $userModelService->paginateByParams([], []);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(15)
        ->and($result->perPage())->toBe(15);
});

test('paginate by params with multiple pages', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 25; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    $result = $userModelService->paginateByParams([], [], 10);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(10)
        ->and($result->total())->toBe(25)
        ->and($result->perPage())->toBe(10)
        ->and($result->lastPage())->toBe(3);
});

test('find one by params returns null when not found', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $result = $userModelService->findOneByParams(['email' => 'nonexistent@example.com']);

    expect($result)->toBeNull();
});

test('get one by params with order by throws exception', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'alice@example.com',
        'name' => 'Alice',
        'password' => 'password123',
    ]);

    $userModelService->getOneByParams(['email' => 'alice@example.com'], [], ['name' => 'desc']);
})->throws(DynamoDbNotSupported::class, 'ORDER BY');

test('find one by params with order by throws exception', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'alice@example.com',
        'name' => 'Alice',
        'password' => 'password123',
    ]);

    $userModelService->findOneByParams(['email' => 'alice@example.com'], [], ['name' => 'desc']);
})->throws(DynamoDbNotSupported::class, 'ORDER BY');

test('paginate by params with second page', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 25; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    Request::merge(['page' => 2]);

    $result = $userModelService->paginateByParams([], [], 10);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(10)
        ->and($result->currentPage())->toBe(2)
        ->and($result->total())->toBe(25);
});

test('paginate by params with invalid page defaults to page 1', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 10; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    Request::merge(['page' => 0]);

    $result = $userModelService->paginateByParams([], [], 5);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->currentPage())->toBe(1)
        ->and($result->items())->toHaveCount(5);
});

test('paginate by params with non numeric page defaults to page 1', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    for ($i = 0; $i < 10; $i++) {
        $userModelService->create([
            'email' => sprintf('user%d@example.com', $i),
            'id' => fake()->uuid(),
            'name' => 'User ' . $i,
            'password' => 'password123',
        ]);
    }

    Request::merge(['page' => 'invalid']);

    $result = $userModelService->paginateByParams([], [], 5);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->currentPage())->toBe(1)
        ->and($result->items())->toHaveCount(5);
});

test('raw mass update throws exception', function (): void {
    $userModelService = app(UserDynamoModelService::class);

    $userModelService->create([
        'id' => fake()->uuid(),
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    $userModelService->getModelManager()->rawMassUpdate([
        ['id' => 'test-id', 'name' => 'Updated Name'],
    ]);
})->throws(DynamoDbNotSupported::class, 'raw mass update');

test('get model manager returns correct instance', function (): void {
    $userModelService = app(UserDynamoModelService::class);
    $manager = $userModelService->getModelManager();

    expect($manager)->toBeInstanceOf(UserDynamoModelManager::class);
});

test('get repository returns correct instance', function (): void {
    $userModelService = app(UserDynamoModelService::class);
    $repository = $userModelService->getRepository();

    expect($repository)->toBeInstanceOf(UserDynamoRepository::class);
});

test('get model class returns correct class name', function (): void {
    $userModelService = app(UserDynamoModelService::class);
    $reflection = new ReflectionClass($userModelService);
    $method = $reflection->getMethod('getModelClass');
    $method->setAccessible(true);

    $modelClass = $method->invoke($userModelService);

    expect($modelClass)->toBe(UserDynamoModel::class);
});
