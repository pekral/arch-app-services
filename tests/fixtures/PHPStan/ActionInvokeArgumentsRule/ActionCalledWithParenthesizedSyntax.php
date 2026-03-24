<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeArgumentsRule;

use Pekral\Arch\Action\ArchAction;

final readonly class ParenthesizedAction implements ArchAction
{

    public function __invoke(): void
    {
    }

}

final class ActionCalledWithParenthesizedSyntax
{

    public function run(ParenthesizedAction $action, string $project): void
    {
        // Invalid: parenthesized syntax with arguments — ($action)($arg1, $arg2)
        ($action)($project, 'extra');
    }

}
