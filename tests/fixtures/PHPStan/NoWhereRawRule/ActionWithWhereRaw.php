<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoWhereRawRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;

/**
 * Action that uses whereRaw — must trigger errors from this rule.
 */
final readonly class ActionWithWhereRaw implements ArchAction
{

    public function __invoke(): void
    {
        User::query()->whereRaw('email LIKE ?', ['%test%'])->get();
    }

}
