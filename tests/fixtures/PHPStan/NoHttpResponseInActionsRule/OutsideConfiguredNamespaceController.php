<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule\LegacyOutside;

use Illuminate\Http\JsonResponse;

// Valid: the namespace only shares a name prefix with the configured Action namespace, and the class is not an ArchAction.
final readonly class OutsideConfiguredNamespaceController
{

    public function __invoke(): JsonResponse
    {
        return new JsonResponse();
    }

}
