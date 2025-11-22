<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\DynamoDb\Repository;

use BaoPham\DynamoDb\DynamoDbQueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Pekral\Arch\Examples\Models\DynamoDb\User;
use Pekral\Arch\Examples\Services\DynamoDb\User\UserRepository;
use Pekral\Arch\Exceptions\GroupByNotSupported;
use Pekral\Arch\Exceptions\OrderByNotSupported;
use Pekral\Arch\Exceptions\RelationsNotSupported;

test('get one by params without order by works', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => '1', 'email' => 'alice@example.com', 'name' => 'Alice']);
    $user->save();

    $foundUser = $userRepository->getOneByParams(['email' => 'alice@example.com'], [], []);

    expect($foundUser)->toBeInstanceOf(User::class)
        ->and($foundUser->email)->toBe('alice@example.com');
});

test('get one by params throws exception when with relations provided', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => '2', 'email' => 'test@example.com', 'name' => 'Test']);
    $user->save();

    expect(fn () => $userRepository->getOneByParams(['email' => 'test@example.com'], ['some_relation'], []))
        ->toThrow(RelationsNotSupported::class);
});

test('find one by params without order by works', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => '3', 'email' => 'bob@example.com', 'name' => 'Bob']);
    $user->save();

    $foundUser = $userRepository->findOneByParams(['email' => 'bob@example.com'], [], []);

    expect($foundUser)->toBeInstanceOf(User::class);
    assert($foundUser !== null);
    expect($foundUser->email)->toBe('bob@example.com');
});

test('find one by params throws exception when with relations provided', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => '4', 'email' => 'test2@example.com', 'name' => 'Test2']);
    $user->save();

    expect(fn () => $userRepository->findOneByParams(['email' => 'test2@example.com'], ['some_relation'], []))
        ->toThrow(RelationsNotSupported::class);
});

test('count by params throws exception when group by provided', function (): void {
    $userRepository = app(UserRepository::class);
    $user1 = new User(['id' => '5', 'name' => 'John', 'email' => 'john1@example.com']);
    $user1->save();
    $user2 = new User(['id' => '6', 'name' => 'John', 'email' => 'john2@example.com']);
    $user2->save();

    expect(fn () => $userRepository->countByParams(['name' => 'John'], ['name']))
        ->toThrow(GroupByNotSupported::class);
});

test('count by params with filters', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => '7', 'name' => 'Jane', 'email' => 'jane@example.com']);
    $user->save();

    $count = $userRepository->countByParams(['name' => 'Jane'], []);

    expect($count)->toBeGreaterThanOrEqual(1);
});

test('paginate by params returns paginated results', function (): void {
    $userRepository = app(UserRepository::class);
    $user1 = new User(['id' => '8', 'name' => 'User 1', 'email' => 'user1@example.com']);
    $user1->save();
    $user2 = new User(['id' => '9', 'name' => 'User 2', 'email' => 'user2@example.com']);
    $user2->save();

    $result = $userRepository->paginateByParams([]);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBeGreaterThanOrEqual(2);
});

test('paginate by params with filters', function (): void {
    $userRepository = app(UserRepository::class);
    $uniqueEmail = 'john-filter-' . uniqid() . '@example.com';
    $user = new User(['id' => 'filter-' . uniqid(), 'name' => 'John', 'email' => $uniqueEmail]);
    $user->save();

    $result = $userRepository->paginateByParams(['email' => $uniqueEmail]);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBeGreaterThanOrEqual(1);
});

test('paginate by params with custom per page limits results', function (): void {
    $userRepository = app(UserRepository::class);
    
    for ($i = 1; $i <= 10; $i++) {
        $user = new User(['id' => 'perpage-' . $i, 'name' => 'User ' . $i, 'email' => sprintf('user%d-perpage@example.com', $i)]);
        $user->save();
    }

    $result = $userRepository->paginateByParams([], [], 5);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(5)
        ->and($result->count())->toBeLessThanOrEqual(5);
});

test('paginate by params with small per page value', function (): void {
    $userRepository = app(UserRepository::class);
    
    $user1 = new User(['id' => 'perpage-small-1', 'name' => 'Small1', 'email' => 'small1@example.com']);
    $user1->save();
    $user2 = new User(['id' => 'perpage-small-2', 'name' => 'Small2', 'email' => 'small2@example.com']);
    $user2->save();
    $user3 = new User(['id' => 'perpage-small-3', 'name' => 'Small3', 'email' => 'small3@example.com']);
    $user3->save();

    $result = $userRepository->paginateByParams([], [], 2);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(2)
        ->and($result->count())->toBeLessThanOrEqual(2);
});

test('paginate by params with large per page value', function (): void {
    $userRepository = app(UserRepository::class);
    
    for ($i = 1; $i <= 20; $i++) {
        $user = new User(['id' => 'perpage-large-' . $i, 'name' => 'LargeUser ' . $i, 'email' => sprintf('large%d@example.com', $i)]);
        $user->save();
    }

    $result = $userRepository->paginateByParams([], [], 50);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(50)
        ->and($result->count())->toBeLessThanOrEqual(50);
});

test('paginate by params with null per page uses default', function (): void {
    $userRepository = app(UserRepository::class);
    
    for ($i = 1; $i <= 20; $i++) {
        $user = new User(['id' => 'perpage-null-' . $i, 'name' => 'NullUser ' . $i, 'email' => sprintf('null%d@example.com', $i)]);
        $user->save();
    }

    $result = $userRepository->paginateByParams([], [], null);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(15)
        ->and($result->count())->toBeLessThanOrEqual(15);
});

test('paginate by params with per page and filters', function (): void {
    $userRepository = app(UserRepository::class);
    
    for ($i = 1; $i <= 10; $i++) {
        $user = new User(['id' => 'perpage-filter-' . $i, 'name' => 'Filtered', 'email' => sprintf('filtered%d@example.com', $i)]);
        $user->save();
    }

    $result = $userRepository->paginateByParams(['name' => 'Filtered'], [], 3);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(3)
        ->and($result->count())->toBeLessThanOrEqual(3);
});

test('paginate by params with per page one returns single result', function (): void {
    $userRepository = app(UserRepository::class);
    
    $user1 = new User(['id' => 'perpage-one-1', 'name' => 'One1', 'email' => 'one1@example.com']);
    $user1->save();
    $user2 = new User(['id' => 'perpage-one-2', 'name' => 'One2', 'email' => 'one2@example.com']);
    $user2->save();
    $user3 = new User(['id' => 'perpage-one-3', 'name' => 'One3', 'email' => 'one3@example.com']);
    $user3->save();

    $result = $userRepository->paginateByParams([], [], 1);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(1)
        ->and($result->count())->toBe(1);
});

test('paginate by params with per page zero or negative uses default', function (): void {
    $userRepository = app(UserRepository::class);
    
    $user1 = new User(['id' => 'perpage-zero-1', 'name' => 'Zero1', 'email' => 'zero1@example.com']);
    $user1->save();
    $user2 = new User(['id' => 'perpage-zero-2', 'name' => 'Zero2', 'email' => 'zero2@example.com']);
    $user2->save();

    $result = $userRepository->paginateByParams([], [], 0);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(15);
});

test('paginate by params with negative per page uses default', function (): void {
    $userRepository = app(UserRepository::class);
    
    $user1 = new User(['id' => 'perpage-negative-1', 'name' => 'Negative1', 'email' => 'negative1@example.com']);
    $user1->save();

    $result = $userRepository->paginateByParams([], [], -5);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(15);
});

test('paginate by params throws exception when with relations provided', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => '13', 'name' => 'Test', 'email' => 'test3@example.com']);
    $user->save();

    expect(fn () => $userRepository->paginateByParams([], ['some_relation'], null, [], []))
        ->toThrow(RelationsNotSupported::class);
});

test('paginate by params throws exception when group by provided', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => '13-group', 'name' => 'Test', 'email' => 'test3-group@example.com']);
    $user->save();

    expect(fn () => $userRepository->paginateByParams([], [], null, [], ['name']))
        ->toThrow(GroupByNotSupported::class);
});

test('query returns builder instance', function (): void {
    $userRepository = app(UserRepository::class);

    $query = $userRepository->query();

    expect($query)->toBeInstanceOf(DynamoDbQueryBuilder::class);
});

test('create query builder returns builder instance', function (): void {
    $userRepository = app(UserRepository::class);

    $query = $userRepository->createQueryBuilder();

    expect($query)->toBeInstanceOf(DynamoDbQueryBuilder::class);
});

test('paginate by params throws exception when order by asc provided', function (): void {
    $userRepository = app(UserRepository::class);
    
    $user1 = new User(['id' => 'order-1', 'name' => 'Alice', 'email' => 'alice-order@example.com']);
    $user1->save();

    expect(fn () => $userRepository->paginateByParams([], [], null, ['name' => 'asc']))
        ->toThrow(OrderByNotSupported::class);
});

test('paginate by params throws exception when order by desc provided', function (): void {
    $userRepository = app(UserRepository::class);
    
    $user1 = new User(['id' => 'order-desc-1', 'name' => 'Zebra', 'email' => 'zebra-order@example.com']);
    $user1->save();

    expect(fn () => $userRepository->paginateByParams([], [], null, ['name' => 'desc']))
        ->toThrow(OrderByNotSupported::class);
});

test('paginate by params throws exception when multiple order by provided', function (): void {
    $userRepository = app(UserRepository::class);
    
    $user1 = new User(['id' => 'order-multi-1', 'name' => 'Test1', 'email' => 'test1-multi@example.com']);
    $user1->save();

    expect(fn () => $userRepository->paginateByParams([], [], null, ['name' => 'asc', 'email' => 'desc']))
        ->toThrow(OrderByNotSupported::class);
});

test('get one by params throws exception when order by asc provided', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => 'order-get-1', 'email' => 'order-get-asc@example.com', 'name' => 'OrderTest']);
    $user->save();

    expect(fn () => $userRepository->getOneByParams(['email' => 'order-get-asc@example.com'], [], ['name' => 'asc']))
        ->toThrow(OrderByNotSupported::class);
});

test('get one by params throws exception when order by desc provided', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => 'order-get-2', 'email' => 'order-get-desc@example.com', 'name' => 'OrderTestDesc']);
    $user->save();

    expect(fn () => $userRepository->getOneByParams(['email' => 'order-get-desc@example.com'], [], ['name' => 'desc']))
        ->toThrow(OrderByNotSupported::class);
});

test('find one by params throws exception when order by asc provided', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => 'order-find-1', 'email' => 'order-find-asc@example.com', 'name' => 'FindOrderTest']);
    $user->save();

    expect(fn () => $userRepository->findOneByParams(['email' => 'order-find-asc@example.com'], [], ['name' => 'asc']))
        ->toThrow(OrderByNotSupported::class);
});

test('find one by params throws exception when order by desc provided', function (): void {
    $userRepository = app(UserRepository::class);
    $user = new User(['id' => 'order-find-2', 'email' => 'order-find-desc@example.com', 'name' => 'FindOrderTestDesc']);
    $user->save();

    expect(fn () => $userRepository->findOneByParams(['email' => 'order-find-desc@example.com'], [], ['name' => 'desc']))
        ->toThrow(OrderByNotSupported::class);
});
