<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;

/**
 * Action for bulk importing users with duplicate handling.
 */
final readonly class BulkImportUsers implements ArchAction
{

    public function __construct(private readonly UserModelService $userModelService)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $userData
     * @return array<int, array<string, mixed>>
     */
    private function prepareUserData(array $userData): array
    {
        $now = now();

        return array_map(static fn (array $data): array => [...$data, 'created_at' => $now, 'updated_at' => $now], $userData);
    }

    /**
     * @param array<int, array<string, mixed>> $userData
     * @return array{
     *     total_processed: int,
     *     created: int,
     *     ignored: int
     * }
     */
    public function __invoke(array $userData): array
    {
        if ($userData === []) {
            return [
                'created' => 0,
                'ignored' => 0,
                'total_processed' => 0,
            ];
        }

        // Prepare data with timestamps
        $preparedData = $this->prepareUserData($userData);

        $existingCount = $this->userModelService->countByParams([]);

        $this->userModelService->getModelManager()->insertOrIgnore($preparedData);

        $newCount = $this->userModelService->countByParams([]);
        $createdCount = $newCount - $existingCount;
        $ignoredCount = count($preparedData) - $createdCount;

        return [
            'created' => $createdCount,
            'ignored' => $ignoredCount,
            'total_processed' => count($preparedData),
        ];
    }

}
