<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Illuminate\Http\JsonResponse;
use Pekral\Arch\Examples\DTO\CreateUserDTO;

// Violation: response inside a union.
final readonly class ReturnsUnionWithResponseAction implements ArchAction
{

    public function __invoke(): CreateUserDTO|JsonResponse
    {
        throw new \LogicException('Fixture only.');
    }

}
