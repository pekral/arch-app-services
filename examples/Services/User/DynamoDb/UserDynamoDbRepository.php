<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\User\DynamoDb;

use Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb;
use Pekral\Arch\Repository\DynamoDb\BaseRepository;

/**
 * @extends \Pekral\Arch\Repository\DynamoDb\BaseRepository<\Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb>
 */
final class UserDynamoDbRepository extends BaseRepository
{

    protected function getModelClassName(): string
    {
        return UserDynamoDb::class;
    }

}
