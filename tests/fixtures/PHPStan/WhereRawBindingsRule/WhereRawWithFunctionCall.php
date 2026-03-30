<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\WhereRawBindingsRule;

use Pekral\Arch\Tests\Models\User;

/**
 * Function call result as whereRaw argument without bindings — must trigger error.
 */
final class WhereRawWithFunctionCall
{

    public function handle(): void
    {
        User::query()->whereRaw($this->buildCondition())->get();
    }

    private function buildCondition(): string
    {
        return 'price > 100';
    }

}
