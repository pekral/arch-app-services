<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\DynamoDb\User;

use Pekral\Arch\Examples\Models\DynamoDb\User;
use Pekral\Arch\Repository\CacheableRepository;
use Pekral\Arch\Repository\DynamoDb\BaseDynamoDbRepository;

/**
 * @extends \Pekral\Arch\Repository\DynamoDb\BaseDynamoDbRepository<\Pekral\Arch\Examples\Models\DynamoDb\User>
 */
final class UserRepository extends BaseDynamoDbRepository
{

    use CacheableRepository;

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
