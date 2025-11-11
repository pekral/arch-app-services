<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\ModelManager;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\UserWithoutMassUpdate;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\Pekral\Arch\Tests\Models\UserWithoutMassUpdate>
 */
final class UserWithoutMassUpdateModelManager extends BaseModelManager
{

    protected function getModelClassName(): string
    {
        return UserWithoutMassUpdate::class;
    }

}
