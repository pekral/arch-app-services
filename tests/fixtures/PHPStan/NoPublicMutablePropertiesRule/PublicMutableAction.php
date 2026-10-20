<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoPublicMutablePropertiesRule;

use Pekral\Arch\Action\ArchAction;

final class PublicMutableAction implements ArchAction
{

    public float $discount = 0;

    public function __invoke(float $price): float
    {
        return $price - $this->discount;
    }

}
