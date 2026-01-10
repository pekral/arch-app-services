<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Actions\User\Errors\UserNotFound;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Result\Result;

/**
 * Retrieves a user by ID with Result pattern for explicit error handling.
 *
 * @see GetUser for exception-based alternative
 */
final readonly class GetUserWithResult implements ArchAction
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @return \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Pekral\Arch\Examples\Actions\User\Errors\UserNotFound>
     */
    public function execute(int $userId): Result
    {
        $user = $this->userModelService->findOneByParams(['id' => $userId]);

        if ($user === null) {
            /** @phpstan-ignore return.type */
            return Result::failure(UserNotFound::withId($userId));
        }

        /** @phpstan-ignore return.type */
        return Result::success($user);
    }

    /**
     * @param array<string, mixed> $filters
     * @return \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Pekral\Arch\Examples\Actions\User\Errors\UserNotFound>
     */
    public function executeByParams(array $filters): Result
    {
        $user = $this->userModelService->findOneByParams($filters);

        if ($user === null) {
            $identifier = json_encode($filters, JSON_THROW_ON_ERROR);

            /** @phpstan-ignore return.type */
            return Result::failure(UserNotFound::withId($identifier));
        }

        /** @phpstan-ignore return.type */
        return Result::success($user);
    }

}
