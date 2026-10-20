<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule;

use Pekral\Arch\Action\ArchAction;

final readonly class ValidReadonlyAction implements ArchAction
{

    public function __construct(private PaymentGateway $paymentGateway)
    {
    }

    public function __invoke(int $amount): void
    {
        $this->paymentGateway->charge($amount);
    }

}
