<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\WhereRawBindingsRule;

use Pekral\Arch\Tests\Models\User;

/**
 * Static string literals in whereRaw — allowed without bindings.
 */
final class WhereRawWithStaticString
{

    public function handle(): void
    {
        User::query()->whereRaw('status = 1')->get();
        User::query()->orWhereRaw('deleted_at IS NULL')->get();
    }

}
