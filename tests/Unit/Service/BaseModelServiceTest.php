<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

test('paginate by params returns paginated results', function (): void {
    $userModelService = app(UserModelService::class);
    User::factory()->count(20)->create();

    $result = $userModelService->paginateByParams();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(15);
});

test('paginate by params with filters', function (): void {
    $userModelService = app(UserModelService::class);
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(5)->create(['name' => 'Jane']);
    
    $result = $userModelService->paginateByParams(['name' => 'John']);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(5);
});

test('paginate by params with custom per page', function (): void {
    $userModelService = app(UserModelService::class);
    User::factory()->count(20)->create();
    
    $result = $userModelService->paginateByParams([], [], 10);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(10);
});

test('paginate by params with order by', function (): void {
    $userModelService = app(UserModelService::class);
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);
    
    $result = $userModelService->paginateByParams([], [], null, ['name' => 'desc']);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(2);
    
    $firstUser = $result->items()[0];
    expect($firstUser)->toBeInstanceOf(User::class)
        ->and($firstUser->name)->toBe('Bob');
});

test('paginate by params with group by', function (): void {
    $userModelService = app(UserModelService::class);
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(3)->create(['name' => 'Jane']);
    
    $result = $userModelService->paginateByParams([], [], null, [], ['name']);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(2);
});

test('delete model removes user', function (): void {
    $userModelService = app(UserModelService::class);
    $user = User::factory()->create(['email' => 'delete@example.com']);

    $result = $userModelService->deleteModel($user);

    expect($result)->toBeTrue()
        ->and(User::query()->where('email', 'delete@example.com')->first())->toBeNull();
});

test('delete model returns false when delete returns null', function (): void {
    $userModelService = app(UserModelService::class);

    $mockUser = Mockery::mock(Model::class);
    $mockUser->shouldReceive('delete')->once()->andReturn(null);

    $result = $userModelService->deleteModel($mockUser);

    expect($result)->toBeFalse();
});

test('bulk delete by params deletes matching records', function (): void {
    $userModelService = app(UserModelService::class);
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(3)->create(['name' => 'Jane']);

    $userModelService->bulkDeleteByParams(['name' => 'John']);

    expect(User::query()->where('name', 'John')->count())->toBe(0)
        ->and(User::query()->where('name', 'Jane')->count())->toBe(3);
});

test('bulk delete by params with multiple conditions', function (): void {
    $userModelService = app(UserModelService::class);
    User::factory()->create(['name' => 'John', 'email' => 'john1@example.com']);
    User::factory()->create(['name' => 'John', 'email' => 'john2@example.com']);
    User::factory()->create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $userModelService->bulkDeleteByParams(['name' => 'John', 'email' => 'john1@example.com']);

    expect(User::query()->where('name', 'John')->where('email', 'john1@example.com')->count())->toBe(0)
        ->and(User::query()->where('name', 'John')->count())->toBe(1);
});

test('bulk delete by params with no matching records does nothing', function (): void {
    $userModelService = app(UserModelService::class);
    User::factory()->count(3)->create(['name' => 'John']);

    $userModelService->bulkDeleteByParams(['name' => 'NonExistent']);

    expect(User::query()->where('name', 'John')->count())->toBe(3);
});

test('bulk delete by params deletes all records when no conditions', function (): void {
    $userModelService = app(UserModelService::class);
    User::factory()->count(5)->create();

    $userModelService->bulkDeleteByParams([]);

    expect(User::count())->toBe(0);
});

test('bulk delete by params with empty table does nothing', function (): void {
    $userModelService = app(UserModelService::class);

    $userModelService->bulkDeleteByParams(['name' => 'John']);

    expect(User::count())->toBe(0);
});
