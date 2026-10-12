<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Illuminate\Contracts\Routing\ResponseFactory;

// Violation: response factory contract.
final readonly class ReturnsResponseFactoryAction implements ArchAction
{

    public function __invoke(): ResponseFactory
    {
        throw new \LogicException('Fixture only.');
    }

}
