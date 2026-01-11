<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\DTO\CreateUserDTO;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class CreateUserWithDTO implements ArchAction
{

    use DataBuilder;

    public function __construct(private UserModelService $userModelService, private VerifyUserAction $verifyUserAction)
    {
    }

    public function execute(CreateUserDTO $dto): User
    {
        $dataNormalized = $this->build(
            $dto->toArray(),
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
