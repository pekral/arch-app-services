<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * Action demonstrating various bulk operations with ModelManager.
 */
final readonly class BulkOperationsDemo
{

    public function __construct(private readonly UserModelManager $userModelManager)
    {
    }

    /**
     * @return array{
     *     bulk_create_result: int,
     *     insert_or_ignore_result: int,
     *     bulk_update_result: int,
     *     final_user_count: int
     * }
     */
    public function execute(): array
    {
        // Clear existing users for clean demo
        User::truncate();

        // Demo 1: Bulk create new users
        $newUsers = [
            ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'password' => 'password123'],
            ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'password' => 'password456'],
            ['name' => 'Charlie Brown', 'email' => 'charlie@example.com', 'password' => 'password789'],
        ];

        $bulkCreateResult = $this->userModelManager->bulkCreate($newUsers);

        // Demo 2: Insert or ignore (some duplicates, some new)
        $mixedUsers = [
            // Duplicate email
            ['name' => 'Alice Johnson Updated', 'email' => 'alice@example.com', 'password' => 'newpassword123'],
            // New user
            ['name' => 'David Wilson', 'email' => 'david@example.com', 'password' => 'password999'],
            // New user
            ['name' => 'Eve Davis', 'email' => 'eve@example.com', 'password' => 'password000'],
        ];

        $this->userModelManager->insertOrIgnore($mixedUsers);

        // Demo 3: Bulk update existing users
        $existingUsers = User::all();
        $updateData = [];

        foreach ($existingUsers as $user) {
            $updateData[] = [
                'id' => $user->id,
                'name' => $user->name . ' (Updated)',
            ];
        }

        $bulkUpdateResult = $this->userModelManager->bulkUpdate($updateData);

        return [
            'bulk_create_result' => $bulkCreateResult,
            'bulk_update_result' => $bulkUpdateResult,
            'final_user_count' => User::count(),
            'insert_or_ignore_result' => count($mixedUsers),
        ];
    }

}
