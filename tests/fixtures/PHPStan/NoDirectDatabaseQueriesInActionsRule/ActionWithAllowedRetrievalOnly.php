<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoDirectDatabaseQueriesInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;

/**
 * Action that calls only safe retrieval methods without conditions — must NOT trigger errors.
 */
final readonly class ActionWithAllowedRetrievalOnly implements ArchAction
{

    public function __invoke(): void
    {
        User::query()->get();
        User::query()->first();
        User::query()->count();
    }

}
