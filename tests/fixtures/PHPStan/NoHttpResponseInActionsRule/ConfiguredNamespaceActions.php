<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule\Legacy;

use Illuminate\Http\JsonResponse;

// Violation: does not implement ArchAction, but lives in the namespace the test configuration lists as an Action namespace.
final readonly class LegacyNamespaceResponseAction
{

    public function __invoke(): JsonResponse
    {
        throw new \LogicException('Fixture only.');
    }

}
