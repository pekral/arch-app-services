<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Illuminate\Contracts\View\View;

// Violation: view contract.
final readonly class ReturnsViewAction implements ArchAction
{

    public function __invoke(): View
    {
        throw new \LogicException('Fixture only.');
    }

}
