<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule;

use Pekral\Arch\Tests\Models\User;

final class ControllerWithQuery
{

    public function index(): void
    {
        User::query()
            ->where('name', 'test')
            ->orderBy('name')
            ->get();
    }

}
