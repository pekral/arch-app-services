<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionClassModifiersRule;

use Pekral\Arch\Action\ArchAction;

abstract class AbstractActionSupport implements ArchAction
{

    abstract public function __invoke(): void;

}
