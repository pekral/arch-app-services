<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule;

use Pekral\Arch\Tests\Models\User;

final class ServiceWithQuery
{

    public function getUsers(): void
    {
        User::query()
            ->whereIn('id', [1, 2, 3])
            ->get();
    }

}
