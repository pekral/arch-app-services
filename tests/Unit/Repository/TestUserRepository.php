<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Repository;

use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\Repository\Mysql\BaseRepository<\Pekral\Arch\Tests\Models\User>
 */
final class TestUserRepository extends BaseRepository
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
