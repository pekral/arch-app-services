<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\User\DynamoDb;

use Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb;
use Pekral\Arch\ModelManager\ModelManager;
use Pekral\Arch\Repository\Repository;
use Pekral\Arch\Service\BaseModelService;

/**
 * @extends \Pekral\Arch\Service\BaseModelService<\Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb>
 */
final readonly class UserDynamoDbModelService extends BaseModelService
{

    public function __construct(private UserDynamoDbModelManager $userDynamoDbModelManager, private UserDynamoDbRepository $userDynamoDbRepository)
    {
    }

    /**
     * @return \Pekral\Arch\ModelManager\DynamoDb\BaseModelManager<\Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb>
     */
    public function getModelManager(): ModelManager
    {
        return $this->userDynamoDbModelManager;
    }

    /**
     * @return \Pekral\Arch\Repository\DynamoDb\BaseRepository<\Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb>
     */
    public function getRepository(): Repository
    {
        return $this->userDynamoDbRepository;
    }

    protected function getModelClass(): string
    {
        return UserDynamoDb::class;
    }

}
