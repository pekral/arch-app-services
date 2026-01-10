<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\DTO\UpdateUserDTO;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class UpdateUserWithDTO implements ArchAction
{

    use DataBuilder;

    public function __construct(private UserModelService $userModelService)
    {
    }

    public function execute(User $model, UpdateUserDTO $dto): User
    {
        $dataNormalized = $this->build(
            $dto->toArray(),
            [
                'email' => LowercaseEmailPipe::class,
                'name' => UcFirstNamePipe::class,
            ],
        );
        $this->userModelService->updateModel($model, $dataNormalized);

        return $model;
    }

}
