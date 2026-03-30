<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Transaction\InTransaction;
use Pekral\Arch\Transaction\TransactionAwareAction;

/**
 * Demonstrates #[InTransaction] attribute usage — the entire __invoke runs
 * inside a database transaction, rolled back automatically on any exception.
 */
final readonly class TransferUserDataAction implements ArchAction
{

    use TransactionAwareAction;

    public function __construct(private UserModelService $userModelService)
    {
    }

    #[InTransaction(attempts: 3)]
    public function __invoke(User $fromUser, User $toUser, string $newName): User
    {
        return $this->executeWithTransactionAttribute(function () use ($fromUser, $toUser, $newName): User {
            $this->userModelService->updateModel($fromUser, ['name' => 'transferred']);
            $this->userModelService->updateModel($toUser, ['name' => $newName]);

            return $toUser;
        });
    }

}
