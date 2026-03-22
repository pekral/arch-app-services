<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule;

use Pekral\Arch\Tests\Models\User;

final class ControllerWithStaticQuery
{

    public function show(int $id): void
    {
        User::find($id);
    }

}
