<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\User;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Service\BaseModelService;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\Service\BaseModelService<\Pekral\Arch\Tests\Models\User>
 */
final readonly class UserModelService extends BaseModelService
{

    public function __construct(private UserModelManager $userModelManager, private UserRepository $userRepository)
    {
    }

    public function getModelManager(): BaseModelManager
    {
        return $this->userModelManager;
    }

    public function getRepository(): BaseRepository
    {
        return $this->userRepository;
    }

    protected function getModelClass(): string
    {
        return User::class;
    }

}
