<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionClassModifiersRule;

use Pekral\Arch\Action\ArchAction;

final readonly class AnonymousActionFactory
{

    public function create(): ArchAction
    {
        return new class implements ArchAction {

            public function __invoke(): void
            {
            }

        };
    }

}
