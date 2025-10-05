<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\ImportUsers;
use Pekral\Arch\Tests\TestCase;

use function fake;

final class ImportUsersTest extends TestCase
{

    private ImportUsers $importUsers;

    public function testImportUsers(): void
    {
        // arrange
        $data = [
            [
                'email' => fake()->email(),
                'name' => fake()->name(),
                'password' => fake()->password(),
            ],
            [
                'email' => fake()->email(),
                'name' => fake()->name(),
                'password' => fake()->password(),
            ],
        ];
        
        // act & assert
        $this->assertSame(count($data), $this->importUsers->handle($data));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->importUsers = app(ImportUsers::class);
    }

}
