<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Symfony\Component\HttpFoundation\Response;

// Violation: Symfony Response.
final readonly class ReturnsSymfonyResponseAction implements ArchAction
{

    public function __invoke(): Response
    {
        throw new \LogicException('Fixture only.');
    }

}
