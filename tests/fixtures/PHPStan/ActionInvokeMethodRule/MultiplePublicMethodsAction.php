<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule;

use Pekral\Arch\Action\ArchAction;

// Intentionally declares two public methods to trigger ActionInvokeMethodRule.
final readonly class MultiplePublicMethodsAction implements ArchAction
{

    public function __invoke(): void
    {
    }

    public function extra(): void
    {
    }

}
