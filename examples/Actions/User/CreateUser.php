<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\DataValidation\DataValidator;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class CreateUser
{

    use DataBuilder;
    use DataValidator;

    public function __construct(private UserModelService $userModelService, private VerifyUserAction $verifyUserAction)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(array $data): User
    {
        $this->validate($data, [
            'email' => 'required|email',
            'name' => 'required|string',
        ], []);
        $dataNormalized = $this->build(
            $data,
            [
                'email' => LowercaseEmailPipe::class,
                'name' => UcFirstNamePipe::class,
            ],
        );
        $model = $this->userModelService->create($dataNormalized);

        $this->verifyUserAction->handle($model);

        return $model;
    }

}
