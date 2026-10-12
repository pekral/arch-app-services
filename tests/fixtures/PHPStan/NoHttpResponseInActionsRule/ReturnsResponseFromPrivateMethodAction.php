<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Illuminate\Http\JsonResponse;
use Pekral\Arch\Action\ArchAction;

// Violation: the rule covers every method, not only __invoke().
final readonly class ReturnsResponseFromPrivateMethodAction implements ArchAction
{

    public function __invoke(): void
    {
        $this->buildResponse();
    }

    private function buildResponse(): JsonResponse
    {
        return new JsonResponse();
    }

}
