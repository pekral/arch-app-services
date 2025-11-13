<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Tests\Models\User;

final readonly class DeleteUserByModelManager implements ArchAction
{

    public function __construct(private UserModelManager $userModelManager)
    {
    }

    public function handle(User $user): bool
    {
        return $this->userModelManager->delete($user);
    }

}
