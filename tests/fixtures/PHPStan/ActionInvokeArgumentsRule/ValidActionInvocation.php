<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeArgumentsRule;

use Pekral\Arch\Action\ArchAction;

final readonly class SomeAction implements ArchAction
{

    public function __invoke(): void
    {
    }

}

final class ValidActionInvocation
{

    public function run(SomeAction $action): void
    {
        // Valid: no arguments passed to the action
        $action();
    }

}
