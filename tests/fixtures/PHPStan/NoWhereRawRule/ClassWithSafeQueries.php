<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoWhereRawRule;

use Pekral\Arch\Tests\Models\User;

/**
 * Class that uses safe Eloquent query builder methods — must NOT trigger errors.
 */
final class ClassWithSafeQueries
{

    public function usingSafeWhere(): void
    {
        User::query()->where('email', 'test@example.com')->get();
    }

    public function usingSafeWhereIn(): void
    {
        User::query()->whereIn('id', [1, 2, 3])->get();
    }

}
