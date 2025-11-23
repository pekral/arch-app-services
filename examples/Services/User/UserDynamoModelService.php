<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\User;

use Pekral\Arch\ModelManager\DynamoDb\BaseModelManager;
use Pekral\Arch\Repository\DynamoDb\BaseRepository;
use Pekral\Arch\Service\BaseModelService;
use Pekral\Arch\Tests\Models\UserDynamoModel;

/**
 * @extends \Pekral\Arch\Service\BaseModelService<\Pekral\Arch\Tests\Models\UserDynamoModel>
 */
final readonly class UserDynamoModelService extends BaseModelService
{

    public function __construct(private UserDynamoRepository $userDynamoRepository, private UserDynamoModelManager $userDynamoModelManager)
    {
    }

    /**
     * @return \Pekral\Arch\ModelManager\DynamoDb\BaseModelManager<\Pekral\Arch\Tests\Models\UserDynamoModel>
     */
    public function getModelManager(): BaseModelManager
    {
        return $this->userDynamoModelManager;
    }

    /**
     * @return \Pekral\Arch\Repository\DynamoDb\BaseRepository<\Pekral\Arch\Tests\Models\UserDynamoModel>
     */
    public function getRepository(): BaseRepository
    {
        return $this->userDynamoRepository;
    }

    protected function getModelClass(): string
    {
        return UserDynamoModel::class;
    }

}
