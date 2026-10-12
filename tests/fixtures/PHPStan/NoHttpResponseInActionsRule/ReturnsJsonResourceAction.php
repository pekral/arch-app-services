<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Illuminate\Http\Resources\Json\JsonResource;

// Violation: JsonResource is Responsable.
final readonly class ReturnsJsonResourceAction implements ArchAction
{

    public function __invoke(): JsonResource
    {
        throw new \LogicException('Fixture only.');
    }

}
