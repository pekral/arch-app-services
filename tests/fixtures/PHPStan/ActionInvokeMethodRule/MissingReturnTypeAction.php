<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule;

use Pekral\Arch\Action\ArchAction;

// Intentionally missing return type on __invoke() to trigger ActionInvokeMethodRule.
final readonly class MissingReturnTypeAction implements ArchAction
{

    public function __invoke()
    {
    }

}
