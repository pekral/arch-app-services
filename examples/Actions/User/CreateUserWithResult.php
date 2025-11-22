<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\DataValidation\DataValidator;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Result\Result;
use Throwable;

final readonly class CreateUserWithResult implements ArchAction
{

    use DataBuilder;
    use DataValidator;

    public function __construct(private UserModelService $userModelService, private VerifyUserAction $verifyUserAction)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Throwable>
     */
    public function execute(array $data): Result
    {
        try {
            $validatedData = $this->validate($data, [
                'email' => 'required|email',
                'name' => 'required|string',
            ], []);
        } catch (ValidationException $e) {
            /** @var \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Throwable> $result */
            $result = Result::failure($e);

            return $result;
        }

        $dataNormalized = $this->build(
            $validatedData,
            [
                'email' => LowercaseEmailPipe::class,
                'name' => UcFirstNamePipe::class,
            ],
        );

        try {
            $model = $this->userModelService->create($dataNormalized);
            $this->verifyUserAction->handle($model);

            /** @var \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Throwable> $result */
            $result = Result::success($model);

            return $result;
        } catch (Throwable $e) {
            /** @var \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Throwable> $result */
            $result = Result::failure($e);

            return $result;
        }
    }

}
