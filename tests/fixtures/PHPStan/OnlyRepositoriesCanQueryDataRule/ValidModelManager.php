<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends BaseModelManager<User>
 */
final class ValidModelManager extends BaseModelManager
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
