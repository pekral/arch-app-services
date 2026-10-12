<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Inertia\Response;

// Violation: Inertia response, declared by InertiaResponseStub.php.
final readonly class ReturnsInertiaResponseAction implements ArchAction
{

    public function __invoke(): Response
    {
        throw new \LogicException('Fixture only.');
    }

}
