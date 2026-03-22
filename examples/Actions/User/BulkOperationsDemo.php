<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;

/**
 * Action demonstrating various bulk operations with ModelManager.
 */
final readonly class BulkOperationsDemo implements ArchAction
{

    public function __construct(private readonly UserModelService $userModelService)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $updateData
     * @return array{
     *     bulk_create_result: int,
     *     insert_or_ignore_result: int,
     *     bulk_update_result: int,
     *     final_user_count: int
     * }
     */
    public function __invoke(array $updateData = []): array
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

        $this->userModelService->getModelManager()->insertOrIgnore($mixedUsers);

        $bulkUpdateResult = $this->userModelService->bulkUpdate($updateData);

        return [
            'bulk_create_result' => $bulkCreateResult,
            'bulk_update_result' => $bulkUpdateResult,
            'final_user_count' => $this->userModelService->countByParams([]),
            'insert_or_ignore_result' => count($mixedUsers),
        ];
    }

}
