<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\WhereRawBindingsRule;

use Pekral\Arch\Tests\Models\User;

/**
 * Dynamic expressions with bindings — allowed.
 */
final class WhereRawWithBindings
{

    public function handle(int $price, string $status): void
    {
        User::query()->whereRaw('price > ?', [$price])->get();
        User::query()->orWhereRaw('status = ?', [$status])->get();
    }

}
