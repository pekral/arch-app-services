<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionClassModifiersRule;

use Pekral\Arch\Action\ArchAction;

class NeitherFinalNorReadonlyAction implements ArchAction
{

    public function __invoke(): void
    {
    }

}
