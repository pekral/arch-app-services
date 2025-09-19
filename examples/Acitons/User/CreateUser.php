<?php

declare(strict_types=1);

namespace Pekral\Arch\Examples\Acitons\User;

use Pekral\Arch\Examples\Services\User\UserService;
use Pekral\Arch\Tests\Models\User;

final readonly class CreateUser
{

    public function __construct(private UserService $userService)
    {

    }

    public function __invoke(string $name, string $email, string $password): User
    {
        return $this->userService->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
    }
}
