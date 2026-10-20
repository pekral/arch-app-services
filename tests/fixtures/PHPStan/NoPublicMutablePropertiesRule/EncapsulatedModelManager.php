<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoPublicMutablePropertiesRule;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Tests\Models\User;

/**
 * @extends \Pekral\Arch\ModelManager\Mysql\BaseModelManager<\Pekral\Arch\Tests\Models\User>
 */
final class EncapsulatedModelManager extends BaseModelManager
{

    public private(set) int $processedCount = 0;

    private int $failedCount = 0;

    public function __construct(public readonly string $name)
    {
    }

    public function markProcessed(bool $failed): int
    {
        if ($failed) {
            $this->failedCount++;
        }

        return ++$this->processedCount + $this->failedCount;
    }

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
