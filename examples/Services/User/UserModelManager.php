<?php

namespace Pekral\Arch\Examples\Services\User;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

final class UserModelManager extends BaseModelManager
{

    protected function getModelClassName(): string
    {
        return User::class;
    }
}