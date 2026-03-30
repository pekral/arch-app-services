<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\WhereRawBindingsRule;

use Pekral\Arch\Tests\Models\User;

/**
 * String concatenation in whereRaw without bindings — must trigger error.
 */
final class WhereRawWithConcatenation
{

    public function handle(string $column): void
    {
        User::query()->whereRaw('price > ' . $column)->get();
    }

}
