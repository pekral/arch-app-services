<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\BulkOperationsDemo;
use Pekral\Arch\Tests\TestCase;

final class BulkOperationsDemoTest extends TestCase
{

    public function testExecute(): void
    {
        $action = app(BulkOperationsDemo::class);

        $result = $action->execute();

        $this->assertSame(3, $result['bulk_create_result']);
        $this->assertSame(3, $result['insert_or_ignore_result']);
        $this->assertSame(5, $result['bulk_update_result']);
        $this->assertSame(5, $result['final_user_count']);

        $this->assertDatabaseHas('users', ['name' => 'Alice Johnson (Updated)']);
        $this->assertDatabaseHas('users', ['name' => 'Bob Smith (Updated)']);
        $this->assertDatabaseHas('users', ['name' => 'Charlie Brown (Updated)']);
    }

}
