<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule;

use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends BaseRepository<User>
 */
final class ValidRepository extends BaseRepository
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

    public function findByName(string $name): void
    {
        User::query()
            ->where('name', $name)
            ->orderBy('name')
            ->get();
    }

}
