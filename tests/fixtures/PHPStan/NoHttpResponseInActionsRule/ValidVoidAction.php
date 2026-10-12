<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;

// Valid: void.
final readonly class ValidVoidAction implements ArchAction
{

    public function __invoke(): void
    {
    }

}
