<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Service;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class BaseModelServiceTest extends TestCase
{

    private TestUserService $testUserService;

    public function testCanCreateUserWithArrayData(): void
    {
        $userData = [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'password123',
        ];

        $user = $this->testUserService->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertSame('password123', $user->password);
        $this->assertGreaterThan(0, $user->id);
    }

    public function testCanCreateUserWithCollectionData(): void
    {
        /** @var \Illuminate\Support\Collection<string, mixed> $userData */
        $userData = new Collection([
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'password' => 'password123',
        ]);

        $user = $this->testUserService->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Jane Doe', $user->name);
        $this->assertSame('jane@example.com', $user->email);
        $this->assertSame('password123', $user->password);
        $this->assertGreaterThan(0, $user->id);
    }

    public function testCanUpdateUserByParamsWithArrayData(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        $updateData = [
            'email' => 'new@example.com',
            'name' => 'New Name',
        ];

        $conditions = ['id' => $user->id];

        $updatedCount = $this->testUserService->updateByParams($updateData, $conditions);

        $this->assertSame(1, $updatedCount);

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function testCanUpdateUserByParamsWithCollectionData(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        /** @var \Illuminate\Support\Collection<string, mixed> $updateData */
        $updateData = new Collection([
            'email' => 'new@example.com',
            'name' => 'New Name',
        ]);

        $conditions = ['id' => $user->id];

        $updatedCount = $this->testUserService->updateByParams($updateData, $conditions);

        $this->assertSame(1, $updatedCount);

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function testCanFindOneUserByParamsWithArrayData(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $params = ['id' => $user->id];
        $foundUser = $this->testUserService->findOneByParams($params);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($user->id, $foundUser->id);
        $this->assertSame('John Doe', $foundUser->name);
    }

    public function testCanFindOneUserByParamsWithCollectionData(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        /** @var \Illuminate\Support\Collection<string, mixed> $params */
        $params = new Collection(['id' => $user->id]);
        $foundUser = $this->testUserService->findOneByParams($params);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($user->id, $foundUser->id);
        $this->assertSame('John Doe', $foundUser->name);
    }

    public function testFindOneByParamsReturnsNullWhenNotFound(): void
    {
        $params = ['id' => 99_999];
        $foundUser = $this->testUserService->findOneByParams($params);

        $this->assertNull($foundUser);
    }

    public function testCanGetOneUserByParamsWithArrayData(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $params = ['id' => $user->id];
        $foundUser = $this->testUserService->getOneByParams($params);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($user->id, $foundUser->id);
        $this->assertSame('John Doe', $foundUser->name);
    }

    public function testCanGetOneUserByParamsWithCollectionData(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        /** @var \Illuminate\Support\Collection<string, mixed> $params */
        $params = new Collection(['id' => $user->id]);
        $foundUser = $this->testUserService->getOneByParams($params);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($user->id, $foundUser->id);
        $this->assertSame('John Doe', $foundUser->name);
    }

    public function testGetOneByParamsThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $params = ['id' => 99_999];
        $this->testUserService->getOneByParams($params);
    }

    public function testCanPaginateUsersByParamsWithArrayData(): void
    {
        User::factory()->count(15)->create();
        User::factory()->create(['name' => 'John Doe']);

        $params = ['name' => 'John Doe'];
        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, \Pekral\Arch\Tests\Models\User> $paginator */
        $paginator = $this->testUserService->paginateByParams($params, [], 5);

        $this->assertSame(1, $paginator->total());
        $this->assertCount(1, $paginator);
        $this->assertSame(5, $paginator->perPage());
        $firstUser = $paginator->first();
        \assert($firstUser instanceof \Pekral\Arch\Tests\Models\User);
        $this->assertSame('John Doe', $firstUser->name);
    }

    public function testCanPaginateUsersByParamsWithCollectionData(): void
    {
        User::factory()->count(15)->create();
        User::factory()->create(['name' => 'John Doe']);

        /** @var \Illuminate\Support\Collection<string, mixed> $params */
        $params = new Collection(['name' => 'John Doe']);
        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, \Pekral\Arch\Tests\Models\User> $paginator */
        $paginator = $this->testUserService->paginateByParams($params, [], 5);

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

        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, \Pekral\Arch\Tests\Models\User> $paginator */
        $paginator = $this->testUserService->paginateByParams([], [], 10);

        $this->assertSame(15, $paginator->total());
        $this->assertCount(10, $paginator);
        $this->assertSame(10, $paginator->perPage());
    }

    public function testUsesDefaultPerPageWhenNotSpecified(): void
    {
        User::factory()->count(20)->create();

        $paginator = $this->testUserService->paginateByParams([]);

        $this->assertSame(15, $paginator->perPage());
    }

    public function testCanDeleteUserByParamsWithArrayData(): void
    {
        $user = User::factory()->create([
            'email' => 'delete@example.com',
            'name' => 'To Delete',
        ]);

        $params = ['id' => $user->id];

        $deleted = $this->testUserService->deleteByParams($params);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function testCanDeleteUserByParamsWithCollectionData(): void
    {
        $user = User::factory()->create([
            'email' => 'delete@example.com',
            'name' => 'To Delete',
        ]);

        /** @var \Illuminate\Support\Collection<string, mixed> $params */
        $params = new Collection(['id' => $user->id]);

        $deleted = $this->testUserService->deleteByParams($params);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function testDeleteByParamsReturnsFalseWhenNoRecordsMatch(): void
    {
        $params = ['id' => 99_999];

        $deleted = $this->testUserService->deleteByParams($params);

        $this->assertFalse($deleted);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUserService = new TestUserService();
    }

}
