<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Acitons\User\Data;

use Pekral\Arch\Data\ActionData;

final class CreateUserActionData extends ActionData
{

    public function __construct(public readonly string $name, public readonly string $email, public readonly string $password)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPipes(): array
    {
        return [];
    }

}
