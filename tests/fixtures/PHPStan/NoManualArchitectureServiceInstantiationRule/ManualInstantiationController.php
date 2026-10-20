<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoManualArchitectureServiceInstantiationRule;

use Pekral\Arch\Examples\Actions\User\GetUser;
use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Examples\Services\User\UserRepository;

final readonly class ManualInstantiationController
{

    public function createRepository(): UserRepository
    {
        return new UserRepository();
    }

    public function createModelManager(): UserModelManager
    {
        return new UserModelManager();
    }

    public function createModelService(): UserModelService
    {
        return new UserModelService(new UserModelManager(), new UserRepository());
    }

    public function createAction(UserModelService $userModelService): GetUser
    {
        return new GetUser($userModelService);
    }

}
