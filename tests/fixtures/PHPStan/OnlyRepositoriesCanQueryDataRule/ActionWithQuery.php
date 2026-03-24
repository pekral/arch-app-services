<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;

final class ActionWithQuery implements ArchAction
{

    public function __invoke(int $id): void
    {
        User::query()
            ->where('name', 'test')
            ->orderBy('name')
            ->get();

        User::find($id);
    }

}
