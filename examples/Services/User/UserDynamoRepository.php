<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\User;

use Pekral\Arch\Repository\DynamoDb\BaseRepository;
use Pekral\Arch\Tests\Models\UserDynamoModel;

/**
 * @extends \Pekral\Arch\Repository\DynamoDb\BaseRepository<\Pekral\Arch\Tests\Models\UserDynamoModel>
 */
final class UserDynamoRepository extends BaseRepository
{

    protected function getModelClassName(): string
    {
        return UserDynamoModel::class;
    }

}
