<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\DTO\UserPairResultDTO;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Transaction\Transactional;

/**
 * Demonstrates explicit Transactional trait usage — wraps multiple write
 * operations in a single atomic transaction via the transaction() helper.
 */
final readonly class CreateUserWithTransaction implements ArchAction
{

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
        return $this->transaction(fn (): UserPairResultDTO => new UserPairResultDTO(
            primary: $this->userModelService->create($primaryData),
            secondary: $this->userModelService->create($secondaryData),
        ));
    }

}
