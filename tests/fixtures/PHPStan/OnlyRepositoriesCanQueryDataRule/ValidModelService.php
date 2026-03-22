<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Service\BaseModelService;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends BaseModelService<User>
 */
final readonly class ValidModelService extends BaseModelService
{

    public function __construct(
        private ValidServiceRepository $repository,
        private ValidServiceModelManager $modelManager,
    ) {
    }

    public function getModelClass(): string
    {
        return User::class;
    }

    /**
     * @return BaseModelManager<User>
     */
    public function getModelManager(): BaseModelManager
    {
        return $this->modelManager;
    }

    /**
     * @return BaseRepository<User>
     */
    public function getRepository(): BaseRepository
    {
        return $this->repository;
    }

}

/**
 * @extends BaseRepository<User>
 */
final class ValidServiceRepository extends BaseRepository
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}

/**
 * @extends BaseModelManager<User>
 */
final class ValidServiceModelManager extends BaseModelManager
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
