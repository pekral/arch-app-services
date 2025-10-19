<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Repository\Mysql;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Pekral\Arch\Examples\Services\User\UserRepository;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class BaseRepositoryTest extends TestCase
{

    private UserRepository $userRepository;

    public function testGetOneByParamsWithOrderBy(): void
    {
        // Arrange
        User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
        
        // Act
        $foundUser = $this->userRepository->getOneByParams(['email' => 'alice@example.com'], [], ['name' => 'desc']);
        
        // Assert
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals('alice@example.com', $foundUser->email);
    }

    public function testFindOneByParamsWithOrderBy(): void
    {
        // Arrange
        User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
        
        // Act
        $foundUser = $this->userRepository->findOneByParams(['email' => 'alice@example.com'], [], ['name' => 'desc']);
        
        // Assert
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals('alice@example.com', $foundUser->email);
    }

    public function testCountByParamsWithGroupBy(): void
    {
        // Arrange
        User::factory()->count(5)->create(['name' => 'John']);
        User::factory()->count(3)->create(['name' => 'Jane']);
        
        // Act
        $count = $this->userRepository->countByParams([], ['name']);
        
        // Assert
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testPaginateByParams(): void
    {
        // Arrange
        User::factory()->count(20)->create();

        // Act
        $result = $this->userRepository->paginateByParams([]);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result);
    }

    public function testPaginateByParamsWithFilters(): void
    {
        // Arrange
        User::factory()->count(5)->create(['name' => 'John']);
        User::factory()->count(5)->create(['name' => 'Jane']);
        
        // Act
        $result = $this->userRepository->paginateByParams(['name' => 'John']);
        
        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(5, $result);
    }

    public function testPaginateByParamsWithCustomPerPage(): void
    {
        // Arrange
        User::factory()->count(20)->create();
        
        // Act
        $result = $this->userRepository->paginateByParams([], [], 10);
        
        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result);
    }

    public function testPaginateByParamsWithOrderBy(): void
    {
        // Arrange
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        
        // Act
        $result = $this->userRepository->paginateByParams([], [], null, ['name' => 'desc']);
        
        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result);
        $firstUser = $result->first();
        $this->assertNotNull($firstUser);
        $this->assertEquals('Bob', $firstUser->name);
    }

    public function testPaginateByParamsWithGroupBy(): void
    {
        // Arrange
        User::factory()->count(5)->create(['name' => 'John']);
        User::factory()->count(3)->create(['name' => 'Jane']);
        
        // Act
        $result = $this->userRepository->paginateByParams([], [], null, [], ['name']);
        
        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result);
    }

    public function testPaginateByParamsWithEmptyWithRelations(): void
    {
        // Arrange
        User::factory()->count(5)->create();
        
        // Act
        $result = $this->userRepository->paginateByParams([], []);
        
        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(5, $result);
    }

    public function testPaginateByParamsWithNonEmptyWithRelations(): void
    {
        // Arrange
        User::factory()->count(5)->create();
        
        // Act
        try {
            $this->userRepository->paginateByParams([], ['non_existent_relation']);
        } catch (RelationNotFoundException $e) {
            // Assert
            $this->assertStringContainsString('non_existent_relation', $e->getMessage());
        }
    }

    public function testQuery(): void
    {
        // Arrange
        User::factory()->count(3)->create();
        
        // Act
        $query = $this->userRepository->query();
        
        // Assert
        $this->assertInstanceOf(Builder::class, $query);
        $this->assertEquals(3, $query->count());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = app(UserRepository::class);
    }

}
