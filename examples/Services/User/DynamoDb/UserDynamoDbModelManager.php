<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\User\DynamoDb;

use Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb;
use Pekral\Arch\ModelManager\DynamoDb\BaseModelManager;

/**
 * @extends \Pekral\Arch\ModelManager\DynamoDb\BaseModelManager<\Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb>
 */
final class UserDynamoDbModelManager extends BaseModelManager
{

    protected function getModelClassName(): string
    {
        return UserDynamoDb::class;
    }

}
