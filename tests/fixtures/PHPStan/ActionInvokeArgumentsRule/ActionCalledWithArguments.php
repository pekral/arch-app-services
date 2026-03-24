<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeArgumentsRule;

use Pekral\Arch\Action\ArchAction;

final readonly class ActionWithParams implements ArchAction
{

    public function __invoke(): void
    {
    }

}

final class ActionCalledWithArguments
{

    public function run(ActionWithParams $action): void
    {
        // Invalid: arguments passed to action invocation
        $action('foo', 'bar');
    }

}
