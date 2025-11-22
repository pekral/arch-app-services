<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Repository\DynamoDb;

use Pekral\Arch\Examples\Models\DynamoDb\User;
use Pekral\Arch\Repository\DynamoDb\BaseDynamoDbRepository;

class TestDynamoDbRepository extends BaseDynamoDbRepository
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
