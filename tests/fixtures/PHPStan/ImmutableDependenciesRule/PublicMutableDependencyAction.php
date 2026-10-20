<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule;

use Pekral\Arch\Action\ArchAction;

final class PublicMutableDependencyAction implements ArchAction
{

    public PaymentGateway $paymentGateway;

    public function __invoke(int $amount): void
    {
        $this->paymentGateway->charge($amount);
    }

}
