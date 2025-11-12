<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Service;

use Illuminate\Pagination\LengthAwarePaginator;
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
