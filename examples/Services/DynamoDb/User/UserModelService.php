<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\DynamoDb\User;

use Pekral\Arch\Examples\Models\DynamoDb\User;
use Pekral\Arch\ModelManager\ModelManager;
use Pekral\Arch\Repository\Repository;
use Pekral\Arch\Service\BaseModelService;

/**
 * @extends \Pekral\Arch\Service\BaseModelService<\Pekral\Arch\Examples\Models\DynamoDb\User>
 */
final readonly class UserModelService extends BaseModelService
{

    public function __construct(private UserModelManager $userModelManager, private UserRepository $userRepository)
    {
    }

    public function getModelManager(): ModelManager
    {
        return $this->userModelManager;
    }

    public function getRepository(): Repository
    {
        return $this->userRepository;
    }

    protected function getModelClass(): string
    {
        return User::class;
    }

}
