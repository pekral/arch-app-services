<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionsMustImplementArchActionRule\Actions;

use Pekral\Arch\Action\ArchAction;

final readonly class ValidArchAction implements ArchAction
{

    public function __invoke(): object
    {
        return new class {
        };
    }

}
