<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Violation: Symfony StreamedResponse subclass.
final readonly class ReturnsStreamedResponseAction implements ArchAction
{

    public function __invoke(): StreamedResponse
    {
        throw new \LogicException('Fixture only.');
    }

}
