<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionsMustImplementArchActionRule\Actions;

// Intentionally omits ArchAction to trigger ActionsMustImplementArchActionRule.
final readonly class MissingInterfaceAction
{

    public function __invoke(): void
    {
    }

}
