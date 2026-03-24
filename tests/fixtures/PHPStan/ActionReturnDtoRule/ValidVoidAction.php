<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionReturnDtoRule;

use Pekral\Arch\Action\ArchAction;

// Valid — returning void is allowed.
final readonly class ValidVoidAction implements ArchAction
{

    public function __invoke(): void
    {
    }

}
