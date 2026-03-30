<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\DTO\UserPairResultDTO;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Transaction\NestedTransactional;
use Pekral\Arch\Transaction\Transactional;
use Throwable;

/**
 * Demonstrates NestedTransactional trait usage — an outer transaction wraps
 * two independent savepoint blocks so each can be rolled back individually
 * without aborting the entire operation.
 */
final readonly class CreateUserWithNestedTransaction implements ArchAction
{

    use NestedTransactional;
    use Transactional;

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $primaryData
     * @param array<string, mixed> $secondaryData
     */
    public function __invoke(array $primaryData, array $secondaryData): UserPairResultDTO
    {
        return $this->transaction(function () use ($primaryData, $secondaryData): UserPairResultDTO {
            $primary = $this->savepoint('create_primary', fn (): User => $this->userModelService->create($primaryData));

            $secondary = null;

            try {
                $secondary = $this->savepoint('create_secondary', fn (): User => $this->userModelService->create($secondaryData));
            } catch (Throwable) {
                // Secondary creation failed — primary is preserved via savepoint rollback.
            }

            return new UserPairResultDTO(primary: $primary, secondary: $secondary);
        });
    }

}
