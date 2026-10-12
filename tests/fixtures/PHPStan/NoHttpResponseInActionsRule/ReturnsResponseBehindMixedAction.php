<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Illuminate\Http\JsonResponse;
use Pekral\Arch\Action\ArchAction;

// Violation: the declared type `mixed` does not hide the returned response.
final readonly class ReturnsResponseBehindMixedAction implements ArchAction
{

    public function __invoke(): mixed
    {
        return new JsonResponse(['a' => 1]);
    }

}
