<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule;

use Pekral\Arch\Action\ArchAction;

readonly class NotFinalAction implements ArchAction
{

    public function __invoke(): void
    {
    }

}
