<?php

namespace Pekral\Arch\Examples\Services\User;

use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Tests\Models\User;

final class UserRepository extends BaseRepository
{

    protected function getModelClassName(): string
    {
        return User::class;
    }
}