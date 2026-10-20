<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\Pekral\Arch\Tests\Models\User>
 */
final class ReadonlyPropertyModelManager extends BaseModelManager
{

    private ?User $lastCreatedUser = null;

    public function __construct(private readonly PaymentGateway $paymentGateway)
    {
    }

    public function chargeFee(User $user): ?User
    {
        $this->paymentGateway->charge(1);
        $previousUser = $this->lastCreatedUser;
        $this->lastCreatedUser = $user;

        return $previousUser;
    }

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
