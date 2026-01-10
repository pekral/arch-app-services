<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Progress;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Progress\ProgressTracker;
use Pekral\Arch\Progress\TracksProgress;

final readonly class ImportUsersWithProgress implements ArchAction
{

    use TracksProgress;

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<int, array{
     *     name: string,
     *     email: string
     * }> $users
     */
    public function execute(array $users, ?ProgressTracker $tracker = null): int
    {
        $this->processBatch($users, function (array $userData): void {
            $this->userModelService->create($userData);
        }, $tracker);

        return count($users);
    }

}
