<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoWhereRawRule;

use Pekral\Arch\Tests\Models\User;

/**
 * Class that uses forbidden whereRaw/orWhereRaw methods — must trigger errors.
 */
final class ClassWithWhereRaw
{

    public function usingWhereRaw(): void
    {
        User::query()->whereRaw('email = ?', ['test@example.com'])->get();
    }

    public function usingOrWhereRaw(): void
    {
        User::query()->orWhereRaw('name = ?', ['test'])->get();
    }

}
