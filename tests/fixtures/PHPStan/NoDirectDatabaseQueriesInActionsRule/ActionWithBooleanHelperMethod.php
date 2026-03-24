<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoDirectDatabaseQueriesInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;

/**
 * Action that calls a boolean helper method on a loaded model instance — must NOT trigger an error.
 * Helper methods read already-hydrated model state and do not issue a database query.
 */
final readonly class ActionWithBooleanHelperMethod implements ArchAction
{

    public function __invoke(User $user): bool
    {
        return $user->isActive();
    }

}
