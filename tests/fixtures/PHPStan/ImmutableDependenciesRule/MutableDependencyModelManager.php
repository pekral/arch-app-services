<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\Pekral\Arch\Tests\Models\User>
 */
final class MutableDependencyModelManager extends BaseModelManager
{

    public function __construct(private PaymentGateway $paymentGateway)
    {
    }

    public function chargeFee(): void
    {
        $this->paymentGateway->charge(1);
    }

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
