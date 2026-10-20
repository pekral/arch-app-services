<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule;

use Pekral\Arch\Service\BaseModelService;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\Service\BaseModelService<\Pekral\Arch\Tests\Models\User>
 */
abstract readonly class SetterInjectedModelService extends BaseModelService
{

    private PaymentGateway $paymentGateway;

    public function initializePaymentGateway(PaymentGateway $paymentGateway): void
    {
        $this->paymentGateway = $paymentGateway;
    }

    protected function getModelClass(): string
    {
        $this->paymentGateway->charge(0);

        return User::class;
    }

}
