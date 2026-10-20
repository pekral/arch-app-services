<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoPublicMutablePropertiesRule;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\Pekral\Arch\Tests\Models\User>
 */
final class PublicMutableModelManager extends BaseModelManager
{

    public function __construct(public ?string $connectionName = null)
    {
    }

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
