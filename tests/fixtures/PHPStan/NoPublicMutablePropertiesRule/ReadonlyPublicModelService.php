<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoPublicMutablePropertiesRule;

use Pekral\Arch\Service\BaseModelService;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\Service\BaseModelService<\Pekral\Arch\Tests\Models\User>
 */
abstract readonly class ReadonlyPublicModelService extends BaseModelService
{

    public function __construct(public string $name)
    {
    }

    protected function getModelClass(): string
    {
        return User::class;
    }

}
