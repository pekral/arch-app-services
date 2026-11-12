<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoSimpleLogicActionsRule;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

final readonly class AccountReader
{

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function accountNames(): Collection
    {
        return new Collection(['main']);
    }

}

final readonly class AuditLogger
{

    public function record(string $message): void
    {
        echo $message;
    }

}

final readonly class AccountDataValidator
{

    public function assertActive(string $accountName): void
    {
        echo $accountName;
    }

}

final readonly class ExpireCouponsJob implements ShouldQueue
{

    public function __construct(public string $accountName)
    {
    }

}
