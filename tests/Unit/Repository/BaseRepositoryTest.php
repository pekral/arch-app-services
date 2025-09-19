<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Repository;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class BaseRepositoryTest extends TestCase
{

    private TestUserRepository $testUserRepository;

    public function testCanPaginateUsersByParams(): void
    {
        User::factory()->count(15)->create();
        User::factory()->create(['name' => 'John Doe']);

        $params = ['name' => 'John Doe'];
        $paginator = $this->testUserRepository->paginateByParams($params, [], 5);

        $this->assertSame(1, $paginator->total());
        $this->assertCount(1, $paginator);
        $this->assertSame(5, $paginator->perPage());
        $firstUser = $paginator->first();
        \assert($firstUser instanceof \Pekral\Arch\Tests\Models\User);
        $this->assertSame('John Doe', $firstUser->name);
    }

    public function testCanPaginateAllUsersWhenNoParams(): void
    {
        User::factory()->count(15)->create();

        $paginator = $this->testUserRepository->paginateByParams([], [], 10);

        $this->assertSame(15, $paginator->total());
        $this->assertCount(10, $paginator);
        $this->assertSame(10, $paginator->perPage());
    }

    public function testCanGetOneUserByParams(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $params = ['id' => $user->id];
        $foundUser = $this->testUserRepository->getOneByParams($params);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($user->id, $foundUser->id);
        $this->assertSame('John Doe', $foundUser->name);
    }

    public function testGetOneByParamsThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $params = ['id' => 99_999];
        $this->testUserRepository->getOneByParams($params);
    }

    public function testCanFindOneUserByParams(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $params = ['id' => $user->id];
        $foundUser = $this->testUserRepository->findOneByParams($params);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($user->id, $foundUser->id);
        $this->assertSame('John Doe', $foundUser->name);
    }

    public function testFindOneByParamsReturnsNullWhenNotFound(): void
    {
        $params = ['id' => 99_999];
        $foundUser = $this->testUserRepository->findOneByParams($params);

        $this->assertNull($foundUser);
    }

    public function testCanWorkWithCollectionParams(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        /** @var \Illuminate\Support\Collection<string, mixed> $params */
        $params = new Collection(['id' => $user->id]);
        $foundUser = $this->testUserRepository->findOneByParams($params);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($user->id, $foundUser->id);
    }

    public function testCanWorkWithArrayParams(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $params = ['id' => $user->id];
        $foundUser = $this->testUserRepository->findOneByParams($params);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($user->id, $foundUser->id);
    }

    public function testUsesDefaultItemsPerPage(): void
    {
        User::factory()->count(15)->create();

        $paginator = $this->testUserRepository->paginateByParams([]);

        $this->assertSame(10, $paginator->perPage());
    }

    public function testCanSpecifyCustomItemsPerPage(): void
    {
        User::factory()->count(15)->create();

        $paginator = $this->testUserRepository->paginateByParams([], [], 5);

        $this->assertSame(5, $paginator->perPage());
    }

    public function testCanPaginateWithRelations(): void
    {
        User::factory()->count(5)->create();

        $paginator = $this->testUserRepository->paginateByParams([], [], 10);

        $this->assertSame(5, $paginator->total());
        $this->assertCount(5, $paginator);
    }

    public function testCanFindOneWithRelations(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $params = ['id' => $user->id];
        $foundUser = $this->testUserRepository->findOneByParams($params, []);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($user->id, $foundUser->id);
        $this->assertSame('John Doe', $foundUser->name);
    }

    public function testCanPaginateWithNonEmptyRelations(): void
    {
        User::factory()->count(5)->create();

        $this->expectException(\Illuminate\Database\Eloquent\RelationNotFoundException::class);

        $this->testUserRepository->paginateByParams([], ['posts'], 10);
    }

    public function testCanFindOneWithNonEmptyRelations(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $params = ['id' => $user->id];

        $this->expectException(\Illuminate\Database\Eloquent\RelationNotFoundException::class);

        $this->testUserRepository->findOneByParams($params, ['posts']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUserRepository = new TestUserRepository();
    }

}
