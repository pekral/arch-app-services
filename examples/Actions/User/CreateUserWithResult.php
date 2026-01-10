<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\DataValidation\DataValidator;
use Pekral\Arch\Examples\Actions\User\Errors\DuplicateEmail;
use Pekral\Arch\Examples\Actions\User\Errors\ValidationFailed;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Result\Result;

/**
 * Creates a new user with Result pattern for explicit error handling.
 *
 * @see CreateUser for exception-based alternative
 */
final readonly class CreateUserWithResult implements ArchAction
{

    use DataBuilder;
    use DataValidator;

    public function __construct(private UserModelService $userModelService, private VerifyUserAction $verifyUserAction)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Pekral\Arch\Examples\Actions\User\Errors\UserFailure>
     */
    public function execute(array $data): Result
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

        /** @var string $email */
        $email = $normalizedData['email'];
        $duplicateCheck = $this->checkDuplicateEmail($email);

        if ($duplicateCheck->isFailure()) {
            /** @phpstan-ignore return.type */
            return Result::failure($duplicateCheck->error());
        }

        $user = $this->userModelService->create($normalizedData);
        $this->verifyUserAction->handle($user);

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
                'email' => 'required|email',
                'name' => 'required|string|min:2',
            ], [
                'email.email' => 'Email must be a valid email address.',
                'email.required' => 'Email is required.',
                'name.min' => 'Name must be at least 2 characters.',
                'name.required' => 'Name is required.',
            ]);

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
