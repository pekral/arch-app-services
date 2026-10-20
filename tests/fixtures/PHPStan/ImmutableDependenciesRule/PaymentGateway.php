<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule;

interface PaymentGateway
{

    public function charge(int $amount): void;

}
