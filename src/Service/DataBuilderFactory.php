<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

use function assert;
use function resolve;

/**
 * Factory for creating DataBuilder instances.
 *
 * Provides a centralized way to create and configure data builders.
 */
final readonly class DataBuilderFactory
{

    /**
     * Create a data builder instance.
     *
     * @template TData of array
     * @param class-string<\Pekral\Arch\Service\DataBuilder<TData>> $builderClass
     * @return \Pekral\Arch\Service\DataBuilder<TData>
     */
    public function create(string $builderClass): DataBuilder
    {
        $dataBuilder = resolve($builderClass);
        assert($dataBuilder instanceof DataBuilder);

        return $dataBuilder;
    }

}
