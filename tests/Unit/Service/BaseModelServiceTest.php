<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Service;

use Illuminate\Pagination\LengthAwarePaginator;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

use function assert;

beforeEach(function (): void {
    $this->userModelService = app(UserModelService::class);
});

test('paginate by params returns paginated results', function (): void {
    User::factory()->count(20)->create();

    $result = $this->userModelService->paginateByParams();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(15);
});

test('paginate by params with filters', function (): void {
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(5)->create(['name' => 'Jane']);
    
    $result = $this->userModelService->paginateByParams(['name' => 'John']);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(5);
});

test('paginate by params with custom per page', function (): void {
    User::factory()->count(20)->create();
    
    $result = $this->userModelService->paginateByParams([], [], 10);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(10);
});

test('paginate by params with order by', function (): void {
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);
    
    $result = $this->userModelService->paginateByParams([], [], null, ['name' => 'desc']);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(2);
    $firstUser = $result->items()[0];
    assert($firstUser instanceof User);
    expect($firstUser)->toBeInstanceOf(User::class)
        ->and($firstUser->name)->toBe('Bob');
});

test('paginate by params with group by', function (): void {
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(3)->create(['name' => 'Jane']);
    
    $result = $this->userModelService->paginateByParams([], [], null, [], ['name']);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(2);
});
