<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\User;

use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\Repository\Mysql\BaseRepository<\Pekral\Arch\Tests\Models\User>
 */
final class UserRepository extends BaseRepository
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
