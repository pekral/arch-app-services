<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoMutableStaticStateRule;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\Pekral\Arch\Tests\Models\User>
 */
final class StaticStateModelManager extends BaseModelManager
{

    private static ?User $lastCreatedUser = null;

    public function rememberUser(User $user): ?User
    {
        $previousUser = self::$lastCreatedUser;
        self::$lastCreatedUser = $user;

        return $previousUser;
    }

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
