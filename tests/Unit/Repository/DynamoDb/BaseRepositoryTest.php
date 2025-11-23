<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Repository\DynamoDb;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb;
use Pekral\Arch\Examples\Services\User\DynamoDb\UserDynamoDbModelManager;
use Pekral\Arch\Examples\Services\User\DynamoDb\UserDynamoDbRepository;
use Pekral\Arch\Exceptions\FeatureNotSupportedForDynamoDb;
use Pekral\Arch\Tests\DynamoDbTestCase;

uses(DynamoDbTestCase::class);

test('paginate by params returns paginated results', function (): void {
    $manager = app(UserDynamoDbModelManager::class);
    $manager->create(['id' => 'user-1', 'name' => 'User 1', 'email' => 'user1@example.com', 'active' => true]);
    $manager->create(['id' => 'user-2', 'name' => 'User 2', 'email' => 'user2@example.com', 'active' => true]);

    $repository = app(UserDynamoDbRepository::class);
    $result = $repository->paginateByParams([]);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBeGreaterThanOrEqual(2);
});

test('paginate by params with eager loading throws exception', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $repository->paginateByParams([], ['relation']);
})->throws(FeatureNotSupportedForDynamoDb::class, 'Eager loading');

test('paginate by params with order by throws exception', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $repository->paginateByParams([], [], null, ['name' => 'desc']);
})->throws(FeatureNotSupportedForDynamoDb::class, 'orderBy');

test('paginate by params with group by throws exception', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $repository->paginateByParams([], [], null, [], ['name']);
})->throws(FeatureNotSupportedForDynamoDb::class, 'GroupBy');

test('get one by params returns model', function (): void {
    $manager = app(UserDynamoDbModelManager::class);
    $manager->create(['id' => 'user-get', 'name' => 'Get User', 'email' => 'get@example.com', 'active' => true]);

    $repository = app(UserDynamoDbRepository::class);
    $result = $repository->getOneByParams(['id' => 'user-get']);

    expect($result)->toBeInstanceOf(UserDynamoDb::class)
        ->and($result->id)->toBe('user-get')
        ->and($result->name)->toBe('Get User');
});

test('get one by params with collection returns model', function (): void {
    $manager = app(UserDynamoDbModelManager::class);
    $manager->create(['id' => 'user-collection', 'name' => 'Collection User', 'email' => 'collection@example.com', 'active' => true]);

    $repository = app(UserDynamoDbRepository::class);
    /** @var \Illuminate\Support\Collection<string, mixed> $params */
    $params = collect(['id' => 'user-collection']);
    $result = $repository->getOneByParams($params);

    expect($result)->toBeInstanceOf(UserDynamoDb::class)
        ->and($result->id)->toBe('user-collection');
});

test('get one by params throws exception when model not found', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $repository->getOneByParams(['id' => 'non-existent']);
})->throws(ModelNotFoundException::class);

test('get one by params with eager loading throws exception', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $repository->getOneByParams(['id' => 'user-123'], ['relation']);
})->throws(FeatureNotSupportedForDynamoDb::class, 'Eager loading');

test('get one by params with order by throws exception', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $repository->getOneByParams(['id' => 'user-123'], [], ['name' => 'desc']);
})->throws(FeatureNotSupportedForDynamoDb::class, 'orderBy');

test('find one by params returns model when found', function (): void {
    $manager = app(UserDynamoDbModelManager::class);
    $manager->create(['id' => 'user-find', 'name' => 'Find User', 'email' => 'find@example.com', 'active' => true]);

    $repository = app(UserDynamoDbRepository::class);
    $result = $repository->findOneByParams(['id' => 'user-find']);

    expect($result)->not->toBeNull()
        ->and($result)->toBeInstanceOf(UserDynamoDb::class)
        ->and($result->id)->toBe('user-find');
});

test('find one by params returns null when not found', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $result = $repository->findOneByParams(['id' => 'non-existent']);

    expect($result)->toBeNull();
});

test('find one by params with collection returns model', function (): void {
    $manager = app(UserDynamoDbModelManager::class);
    $manager->create(['id' => 'user-find-collection', 'name' => 'Find Collection User', 'email' => 'findcollection@example.com', 'active' => true]);

    $repository = app(UserDynamoDbRepository::class);
    /** @var \Illuminate\Support\Collection<string, mixed> $params */
    $params = collect(['id' => 'user-find-collection']);
    $result = $repository->findOneByParams($params);

    expect($result)->not->toBeNull()
        ->and($result)->toBeInstanceOf(UserDynamoDb::class)
        ->and($result->id)->toBe('user-find-collection');
});

test('find one by params with eager loading throws exception', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $repository->findOneByParams(['id' => 'user-123'], ['relation']);
})->throws(FeatureNotSupportedForDynamoDb::class, 'Eager loading');

test('find one by params with order by throws exception', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $repository->findOneByParams(['id' => 'user-123'], [], ['name' => 'desc']);
})->throws(FeatureNotSupportedForDynamoDb::class, 'orderBy');

test('count by params returns count', function (): void {
    $manager = app(UserDynamoDbModelManager::class);
    $manager->create(['id' => 'user-count-1', 'name' => 'Count User 1', 'email' => 'count1@example.com', 'active' => true]);
    $manager->create(['id' => 'user-count-2', 'name' => 'Count User 2', 'email' => 'count2@example.com', 'active' => true]);

    $repository = app(UserDynamoDbRepository::class);
    $result = $repository->countByParams([]);

    expect($result)->toBeGreaterThanOrEqual(2);
});

test('count by params with filters returns filtered count', function (): void {
    $manager = app(UserDynamoDbModelManager::class);
    $manager->create(['id' => 'user-count-filter', 'name' => 'Filter User', 'email' => 'filter@example.com', 'active' => true]);

    $repository = app(UserDynamoDbRepository::class);
    $result = $repository->countByParams(['id' => 'user-count-filter']);

    expect($result)->toBe(1);
});

test('count by params with collection returns count', function (): void {
    $manager = app(UserDynamoDbModelManager::class);
    $manager->create(['id' => 'user-count-collection', 'name' => 'Count Collection User', 'email' => 'countcollection@example.com', 'active' => true]);

    $repository = app(UserDynamoDbRepository::class);
    /** @var \Illuminate\Support\Collection<int, mixed> $params */
    $params = collect([['id' => 'user-count-collection']]);
    $result = $repository->countByParams($params);

    expect($result)->toBeGreaterThanOrEqual(1);
});

test('count by params with group by throws exception', function (): void {
    $repository = app(UserDynamoDbRepository::class);

    $repository->countByParams([], ['name']);
})->throws(FeatureNotSupportedForDynamoDb::class, 'GroupBy');
