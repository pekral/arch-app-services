<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeArgumentsRule;

final class NotAnAction
{

    public function __invoke(string $name): void
    {
    }

}

final class NonActionCalledWithArguments
{

    public function run(NotAnAction $obj): void
    {
        // Valid: not an ArchAction, arguments are allowed
        $obj('foo');
    }

}
