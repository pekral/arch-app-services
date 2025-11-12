<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class DeleteUser implements ArchAction
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    public function handle(int|User $user): void
    {
        if ($user instanceof User) {
            $this->userModelService->deleteModel($user);
        }

        $this->userModelService->deleteByParams(['id' => $user]);
    }

}
