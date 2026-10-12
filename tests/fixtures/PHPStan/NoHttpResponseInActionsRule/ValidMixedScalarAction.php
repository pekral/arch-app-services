<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;

// Valid: a broad declared type that returns a scalar is not a response.
final readonly class ValidMixedScalarAction implements ArchAction
{

    public function __invoke(): mixed
    {
        return 42;
    }

}
