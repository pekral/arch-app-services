<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Illuminate\Contracts\Support\Responsable;

// Violation: Responsable contract.
final readonly class ReturnsResponsableAction implements ArchAction
{

    public function __invoke(): Responsable
    {
        throw new \LogicException('Fixture only.');
    }

}
