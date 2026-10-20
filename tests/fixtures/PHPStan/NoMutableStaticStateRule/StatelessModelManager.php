<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoMutableStaticStateRule;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\Pekral\Arch\Tests\Models\User>
 */
final class StatelessModelManager extends BaseModelManager
{

    private const string DEFAULT_CURRENCY = 'CZK';

    private int $processedCount = 0;

    public static function formatPrice(int $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' ' . self::DEFAULT_CURRENCY;
    }

    public function markProcessed(): int
    {
        return ++$this->processedCount;
    }

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
