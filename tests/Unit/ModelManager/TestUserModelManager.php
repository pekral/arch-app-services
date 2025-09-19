<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\ModelManager;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\Pekral\Arch\Tests\Models\User>
 */
final class TestUserModelManager extends BaseModelManager
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
