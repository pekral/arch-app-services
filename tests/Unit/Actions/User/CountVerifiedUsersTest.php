<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\CountVerifiedUsers;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class CountVerifiedUsersTest extends TestCase
{

    private CountVerifiedUsers $countVerifiedUsers;

    public function testCountVerifiedUsers(): void
    {
        // arrange
        User::factory()->count(10)->create(['email_verified_at' => null]);
        $verifiedUsers = User::factory()->count(10)->create(['email_verified_at' => now()]);
        // act & assert
        $this->assertEquals($verifiedUsers->count(), $this->countVerifiedUsers->handle());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->countVerifiedUsers = app(CountVerifiedUsers::class);
    }

}
