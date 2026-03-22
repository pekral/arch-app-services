<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\Examples\Actions\User\Errors\UserError;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Result\Result;
use Pekral\Arch\Tests\Models\User;

/**
 * Creates a user with explicit, type-safe error handling via the Result pattern.
 */
final readonly class CreateUserWithResult implements ArchAction
{

    use DataBuilder;

    public function __construct(private UserModelService $userModelService, private VerifyUserAction $verifyUserAction)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Pekral\Arch\Examples\Actions\User\Errors\UserError>
     */
    public function __invoke(array $data): Result
    {
        if (!isset($data['email']) || !is_string($data['email'])) {
            return Result::failure(UserError::INVALID_DATA);
        }

        $existing = $this->userModelService->findOneByParams(['email' => $data['email']]);

        if ($existing instanceof User) {
            return Result::failure(UserError::EMAIL_ALREADY_EXISTS);
        }

        $dataNormalized = $this->build(
            $data,
            [
                'email' => LowercaseEmailPipe::class,
                'name' => UcFirstNamePipe::class,
            ],
        );

        $model = $this->userModelService->create($dataNormalized);

        ($this->verifyUserAction)($model);

        return Result::success($model);
    }

}
