<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Illuminate\Http\RedirectResponse;

// Violation: Illuminate RedirectResponse.
final readonly class ReturnsRedirectResponseAction implements ArchAction
{

    public function __invoke(): RedirectResponse
    {
        throw new \LogicException('Fixture only.');
    }

}
