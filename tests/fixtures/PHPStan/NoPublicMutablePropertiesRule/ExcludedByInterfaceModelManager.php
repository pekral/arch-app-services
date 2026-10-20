<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoPublicMutablePropertiesRule;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\Pekral\Arch\Tests\Models\User>
 */
final class ExcludedByInterfaceModelManager extends BaseModelManager implements MutableStateAllowed
{

    public int $retries = 0;

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
