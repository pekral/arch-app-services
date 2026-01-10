<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\DataValidation\DataValidator;
use Pekral\Arch\Examples\Actions\User\Errors\DuplicateEmail;
use Pekral\Arch\Examples\Actions\User\Errors\UserNotFound;
use Pekral\Arch\Examples\Actions\User\Errors\ValidationFailed;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Result\Result;
use Pekral\Arch\Tests\Models\User;

/**
 * Updates an existing user with Result pattern for explicit error handling.
 *
 * @see UpdateUser for exception-based alternative
 */
final readonly class UpdateUserWithResult implements ArchAction
{

    use DataBuilder;
    use DataValidator;

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Pekral\Arch\Examples\Actions\User\Errors\UserFailure>
     */
    public function execute(int $userId, array $data): Result
    {
        $userResult = $this->findUser($userId);

        if ($userResult->isFailure()) {
            /** @phpstan-ignore return.type */
            return Result::failure($userResult->error());
        }

        return $this->validateAndUpdate($userResult->value(), $data);
    }

    /**
     * @return \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Pekral\Arch\Examples\Actions\User\Errors\UserNotFound>
     */
    private function findUser(int $userId): Result
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
     * @param array<string, mixed> $data
     * @return \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Pekral\Arch\Examples\Actions\User\Errors\UserFailure>
     */
    private function validateAndUpdate(User $user, array $data): Result
    {
        $validationResult = $this->validateData($data);

        if ($validationResult->isFailure()) {
            /** @phpstan-ignore return.type */
            return Result::failure($validationResult->error());
        }

        $normalizedData = $this->build(
            $data,
            [
                'email' => LowercaseEmailPipe::class,
                'name' => UcFirstNamePipe::class,
            ],
        );

        if (isset($normalizedData['email']) && $normalizedData['email'] !== $user->email) {
            /** @var string $email */
            $email = $normalizedData['email'];
            $duplicateCheck = $this->checkDuplicateEmail($email);

            if ($duplicateCheck->isFailure()) {
                /** @phpstan-ignore return.type */
                return Result::failure($duplicateCheck->error());
            }
        }

        $this->userModelService->updateModel($user, $normalizedData);

        /** @phpstan-ignore return.type */
        return Result::success($user);
    }

    /**
     * @param array<string, mixed> $data
     * @return \Pekral\Arch\Result\Result<array<string, mixed>, \Pekral\Arch\Examples\Actions\User\Errors\ValidationFailed>
     */
    private function validateData(array $data): Result
    {
        try {
            $validated = $this->validate($data, [
                'email' => 'sometimes|email',
                'name' => 'sometimes|string|min:2',
            ], []);

            /** @phpstan-ignore return.type */
            return Result::success($validated);
        } catch (ValidationException $e) {
            /** @phpstan-ignore return.type */
            return Result::failure(ValidationFailed::fromException($e));
        }
    }

    /**
     * @return \Pekral\Arch\Result\Result<null, \Pekral\Arch\Examples\Actions\User\Errors\DuplicateEmail>
     */
    private function checkDuplicateEmail(string $email): Result
    {
        $existingUser = $this->userModelService->findOneByParams(['email' => $email]);

        if ($existingUser !== null) {
            return Result::failure(DuplicateEmail::forEmail($email));
        }

        /** @phpstan-ignore return.type */
        return Result::success(null);
    }

}
