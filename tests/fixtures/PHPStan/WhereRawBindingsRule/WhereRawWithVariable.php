<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\WhereRawBindingsRule;

use Pekral\Arch\Tests\Models\User;

/**
 * Variable as whereRaw argument without bindings — must trigger error.
 */
final class WhereRawWithVariable
{

    public function handle(string $condition): void
    {
        User::query()->whereRaw($condition)->get();
        User::query()->orWhereRaw($condition)->get();
    }

}
