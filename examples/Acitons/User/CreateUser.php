<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Acitons\User;

use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class CreateUser
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    public function __invoke(string $name, string $email, string $password): User
    {
        return $this->userModelService->create([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ]);
    }

}
