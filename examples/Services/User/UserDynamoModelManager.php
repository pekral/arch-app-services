<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\User;

use Pekral\Arch\ModelManager\DynamoDb\BaseModelManager;
use Pekral\Arch\Tests\Models\UserDynamoModel;

/**
 * @extends \Pekral\Arch\ModelManager\DynamoDb\BaseModelManager<\Pekral\Arch\Tests\Models\UserDynamoModel>
 */
final class UserDynamoModelManager extends BaseModelManager
{

    protected function getModelClassName(): string
    {
        return UserDynamoModel::class;
    }

}
