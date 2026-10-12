<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Illuminate\Http\JsonResponse;

// Violation: Illuminate JsonResponse.
final readonly class ReturnsJsonResponseAction implements ArchAction
{

    public function __invoke(): JsonResponse
    {
        throw new \LogicException('Fixture only.');
    }

}
