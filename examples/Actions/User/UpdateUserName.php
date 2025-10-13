<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class UpdateUserName
{

    use DataBuilder;

    public function __construct(private UserModelService $userModelService)
    {
    }

    public function handle(string $name, User $user): void
    {
        $data = $this->build(['name' => $name], [UcFirstNamePipe::class]);
        $this->userModelService->updateModel($user, $data);
    }

}
