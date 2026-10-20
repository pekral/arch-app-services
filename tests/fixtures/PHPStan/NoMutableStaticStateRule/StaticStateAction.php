<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoMutableStaticStateRule;

use Pekral\Arch\Action\ArchAction;

final class StaticStateAction implements ArchAction
{

    /**
     * @var array<int, int>
     */
    public static array $processedOrders = [];

    public function __invoke(int $orderId): void
    {
        self::$processedOrders[] = $orderId;
    }

}
