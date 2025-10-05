<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\RefreshUsers;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function fake;

final class RefreshUsersTest extends TestCase
{

    private RefreshUsers $refreshUsers;

    public function testRefreshUsers(): void
    {
        // arrange
        $users = User::factory()->count(10)->create();
        $refreshedData = $users->map(static fn (User $user): array => [
            'email' => fake()->email(),
            'id' => $user->id,
            'name' => fake()->name(),
            'password' => fake()->password(),
        ]);

        // act & assert
        /** @var array<int, array<mixed>> $data */
        $data = $refreshedData->values()->toArray();
        $this->assertSame($refreshedData->count(), $this->refreshUsers->handle($data));
    }

    public function testImportUsersWithoutData(): void
    {
        // act & assert
        $this->assertSame(0, $this->refreshUsers->handle([]));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshUsers = app(RefreshUsers::class);
    }

}
