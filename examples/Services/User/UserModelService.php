<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Services\User;

use Pekral\Arch\Service\BaseModelService;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\Service\BaseModelService<\Pekral\Arch\Tests\Models\User>
 */
final readonly class UserModelService extends BaseModelService
{

    protected function getModelClass(): string
    {
        return User::class;
    }

}
