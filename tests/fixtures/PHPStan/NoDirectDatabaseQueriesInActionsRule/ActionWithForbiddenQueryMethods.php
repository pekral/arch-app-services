<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoDirectDatabaseQueriesInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;

/**
 * Action that uses forbidden query builder methods directly — must trigger errors.
 */
final readonly class ActionWithForbiddenQueryMethods implements ArchAction
{

    public function __invoke(): void
    {
        User::query()->where('name', 'test')->get();
        User::query()->orderBy('name')->get();
    }

}
