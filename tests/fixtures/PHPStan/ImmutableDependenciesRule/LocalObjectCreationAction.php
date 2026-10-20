<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule;

use Pekral\Arch\Action\ArchAction;
use ArrayObject;

final readonly class LocalObjectCreationAction implements ArchAction
{

    /**
     * @return \ArrayObject<int, int>
     */
    public function __invoke(int $amount): ArrayObject
    {
        return new ArrayObject([$amount]);
    }

}
