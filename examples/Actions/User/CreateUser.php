<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class CreateUser
{

    public function __construct(
        private UserModelService $userModelService,
        private DataBuilder $baseDataBuilder,
        private VerifyUserAction $verifyUserAction,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): User
    {
        $dataNormalized = $this->baseDataBuilder->build($data, [LowercaseEmailPipe::class, UcFirstNamePipe::class]);
        $model = $this->userModelService->create($dataNormalized);

        $this->verifyUserAction->handle($model);

        return $model;
    }

}
