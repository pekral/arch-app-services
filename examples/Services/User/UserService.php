<?php

namespace Pekral\Arch\Examples\Services\User;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Service\BaseModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class UserService extends BaseModelService
{

    public function __construct(
        protected UserModelManager $userModelManager,
        protected UserRepository $userRepository,
    ) {
    }

    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function getModelManager(): BaseModelManager
    {
        return $this->userModelManager;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->userRepository;
    }
}
