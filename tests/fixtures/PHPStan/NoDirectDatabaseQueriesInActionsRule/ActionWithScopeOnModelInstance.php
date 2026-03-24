<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoDirectDatabaseQueriesInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;

/**
 * Action that calls a scope on an already-loaded model instance — must NOT trigger an error.
 * Reading model state via scopes is permitted in Actions.
 */
final readonly class ActionWithScopeOnModelInstance implements ArchAction
{

    public function __invoke(User $user): void
    {
        $user->active();
    }

}
