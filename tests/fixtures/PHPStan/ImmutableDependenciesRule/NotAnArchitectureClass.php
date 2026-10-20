<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule;

final class NotAnArchitectureClass
{

    public PaymentGateway $paymentGateway;

    public function setPaymentGateway(PaymentGateway $paymentGateway): void
    {
        $this->paymentGateway = $paymentGateway;
    }

}
