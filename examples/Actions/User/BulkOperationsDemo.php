<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\DTO\BulkOperationsResultDTO;
use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Examples\Services\User\UserModelService;

/**
 * Action demonstrating various bulk operations with ModelManager.
 */
final readonly class BulkOperationsDemo implements ArchAction
{

    public function __construct(private UserModelService $userModelService, private UserModelManager $userModelManager)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $updateData
     */
    public function __invoke(array $updateData = []): BulkOperationsResultDTO
    {
        $newUsers = [
            ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'password' => 'password123'],
            ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'password' => 'password456'],
            ['name' => 'Charlie Brown', 'email' => 'charlie@example.com', 'password' => 'password789'],
        ];

        $bulkCreateResult = $this->userModelService->bulkCreate($newUsers);

        $mixedUsers = [
            ['name' => 'Alice Johnson Updated', 'email' => 'alice@example.com', 'password' => 'newpassword123'],
            ['name' => 'David Wilson', 'email' => 'david@example.com', 'password' => 'password999'],
            ['name' => 'Eve Davis', 'email' => 'eve@example.com', 'password' => 'password000'],
        ];

        $this->userModelManager->insertOrIgnore($mixedUsers);

        $bulkUpdateResult = $this->userModelService->bulkUpdate($updateData);

        return new BulkOperationsResultDTO(
            bulkCreateResult: $bulkCreateResult,
            insertOrIgnoreResult: count($mixedUsers),
            bulkUpdateResult: $bulkUpdateResult,
            finalUserCount: $this->userModelService->countByParams([]),
        );
    }

}
