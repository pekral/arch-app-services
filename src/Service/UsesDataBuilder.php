<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

use function app;

/**
 * Trait for actions that use DataBuilder pattern.
 *
 * Provides convenient methods for data transformation.
 *
 * @template TData of array
 */
trait UsesDataBuilder
{

    /**
     * Transform data using the specified data builder.
     *
     * @param TData $data
     * @param class-string<\Pekral\Arch\Service\DataBuilder<TData>> $builderClass
     * @return TData
     */
    protected function transformDataWithBuilder(array $data, string $builderClass): array
    {
        return app(DataBuilderFactory::class)
            ->create($builderClass)
            ->build($data);
    }

}
