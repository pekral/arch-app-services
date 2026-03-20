<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule;

use Pekral\Arch\Action\ArchAction;

// Intentionally uses handle() instead of __invoke() to trigger ActionInvokeMethodRule.
final readonly class WrongMethodNameAction implements ArchAction
{

    public function handle(): void
    {
    }

}
