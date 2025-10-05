<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Service;

use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class BaseModelServiceTest extends TestCase
{

    private UserModelService $userModelService;

    public function testPaginateByParams(): void
    {
        // Arrange
        User::factory()->count(20)->create();

        // Act
        $result = $this->userModelService->paginateByParams();

        // Assert
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result->items());
    }

    public function testDeleteModelWithNullResult(): void
    {
        // Arrange
        User::factory()->create();
        
        $reflection = new \ReflectionClass($this->userModelService);
        $method = $reflection->getMethod('deleteModel');
        
        $mockModel = $this->createMock(\Illuminate\Database\Eloquent\Model::class);
        $mockModel->method('delete')->willReturn(null);
        
        // Act
        $result = $method->invoke($this->userModelService, $mockModel);
        
        // Assert
        $this->assertFalse($result);
    }

    public function testPaginateByParamsWithFilters(): void
    {
        // Arrange
        User::factory()->count(5)->create(['name' => 'John']);
        User::factory()->count(5)->create(['name' => 'Jane']);
        
        // Act
        $result = $this->userModelService->paginateByParams(['name' => 'John']);
        
        // Assert
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(5, $result->items());
    }

    public function testPaginateByParamsWithCustomPerPage(): void
    {
        // Arrange
        User::factory()->count(20)->create();
        
        // Act
        $result = $this->userModelService->paginateByParams([], [], 10);
        
        // Assert
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
    }

    public function testPaginateByParamsWithOrderBy(): void
    {
        // Arrange
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        
        // Act
        $result = $this->userModelService->paginateByParams([], [], null, ['name' => 'desc']);
        
        // Assert
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
        $firstUser = $result->items()[0];
        $this->assertInstanceOf(\Pekral\Arch\Tests\Models\User::class, $firstUser);
        $this->assertEquals('Bob', $firstUser->name);
    }

    public function testPaginateByParamsWithGroupBy(): void
    {
        // Arrange
        User::factory()->count(5)->create(['name' => 'John']);
        User::factory()->count(3)->create(['name' => 'Jane']);
        
        // Act
        $result = $this->userModelService->paginateByParams([], [], null, [], ['name']);
        
        // Assert
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModelService = app(UserModelService::class);
    }

}
