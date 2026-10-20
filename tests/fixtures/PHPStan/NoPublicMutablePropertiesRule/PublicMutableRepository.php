<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoPublicMutablePropertiesRule;

use Pekral\Arch\Repository\Repository;

/**
 * @implements \Pekral\Arch\Repository\Repository<\Pekral\Arch\Tests\Models\User>
 */
abstract class PublicMutableRepository implements Repository
{

    public int $defaultLimit = 15;

}
