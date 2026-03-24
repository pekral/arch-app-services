<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoDirectDatabaseQueriesInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;

/**
 * Action that chains a scope on a query builder before a retrieval method — must trigger an error.
 */
final readonly class ActionWithBuilderScopeChain implements ArchAction
{

    public function __invoke(): void
    {
        User::query()->active()->get();
    }

}
