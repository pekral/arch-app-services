<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Service;

use Pekral\Arch\Service\BaseModelService;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\Service\BaseModelService<\Pekral\Arch\Tests\Models\User>
 */
final readonly class TestUserService extends BaseModelService
{

    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function createModelManager(): TestUserModelManager
    {
        return new TestUserModelManager();
    }

    protected function createRepository(): TestUserRepository
    {
        return new TestUserRepository();
    }

}
