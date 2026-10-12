<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Illuminate\Http\JsonResponse;
use Pekral\Arch\Action\ArchAction;
use Stringable;

// Violation: the declared type `Stringable` does not hide the returned response.
final readonly class ReturnsResponseBehindStringableAction implements ArchAction
{

    public function __invoke(): Stringable
    {
        return new JsonResponse(['a' => 1]);
    }

}
