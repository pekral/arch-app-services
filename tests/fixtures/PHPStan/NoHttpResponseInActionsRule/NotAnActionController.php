<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Illuminate\Http\JsonResponse;

// Valid: the class does not implement ArchAction, so building a response is its job.
final readonly class NotAnActionController
{

    public function __invoke(): JsonResponse
    {
        return new JsonResponse();
    }

}
