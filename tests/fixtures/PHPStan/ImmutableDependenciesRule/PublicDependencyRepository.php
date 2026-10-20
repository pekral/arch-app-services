<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule;

use Pekral\Arch\Repository\Repository;

/**
 * @implements \Pekral\Arch\Repository\Repository<\Pekral\Arch\Tests\Models\User>
 */
abstract class PublicDependencyRepository implements Repository
{

    public function __construct(public PaymentGateway $paymentGateway)
    {
    }

}
