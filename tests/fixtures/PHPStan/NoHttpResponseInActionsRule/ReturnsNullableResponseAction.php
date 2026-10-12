<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Illuminate\Http\JsonResponse;

// Violation: nullable response.
final readonly class ReturnsNullableResponseAction implements ArchAction
{

    public function __invoke(): ?JsonResponse
    {
        throw new \LogicException('Fixture only.');
    }

}
