<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\DynamoDb\User;

use Pekral\Arch\Examples\Models\DynamoDb\User;
use Pekral\Arch\ModelManager\DynamoDb\BaseDynamoDbModelManager;

/**
 * @extends \Pekral\Arch\ModelManager\DynamoDb\BaseDynamoDbModelManager<\Pekral\Arch\Examples\Models\DynamoDb\User>
 */
final class UserModelManager extends BaseDynamoDbModelManager
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
