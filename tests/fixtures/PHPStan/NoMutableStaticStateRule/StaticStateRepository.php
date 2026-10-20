<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoMutableStaticStateRule;

use Pekral\Arch\Repository\Repository;

/**
 * @implements \Pekral\Arch\Repository\Repository<\Pekral\Arch\Tests\Models\User>
 */
abstract class StaticStateRepository implements Repository
{

    protected static int $queryCount = 0;

}
