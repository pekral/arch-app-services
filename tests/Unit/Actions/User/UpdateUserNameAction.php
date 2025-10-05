<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class UpdateUserNameAction
{

    public function __construct(private UserModelService $userModelService, private DataBuilder $baseDataBuilder)
    {
    }

    public function handle(string $name, User $user): void
    {
        $data = $this->baseDataBuilder->build(['name' => $name], [UcFirstNamePipe::class]);
        $this->userModelService->updateModel($user, $data);
    }

}
