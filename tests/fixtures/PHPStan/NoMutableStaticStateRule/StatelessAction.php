<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoMutableStaticStateRule;

use Pekral\Arch\Action\ArchAction;

final readonly class StatelessAction implements ArchAction
{

    private const int VAT_PERCENT = 21;

    public function __invoke(int $amount): int
    {
        return self::withVat($amount);
    }

    private static function withVat(int $amount): int
    {
        return intdiv($amount * (100 + self::VAT_PERCENT), 100);
    }

}
