<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionsMustImplementArchActionRule;

// Valid: the class is declared outside the configured Actions namespace.
final readonly class OutsideConfiguredNamespaceService
{

    public function __invoke(): void
    {
    }

}
