<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

/**
 * Interface for data transformation builders.
 *
 * Provides a contract for transforming data using Laravel Pipeline pattern.
 *
 * @template TData of array
 */
interface DataBuilder
{

    /**
     * Transform data using configured pipeline.
     *
     * @param TData $data
     * @return TData
     */
    public function build(array $data): array;

    /**
     * Get the pipes that should be used for data transformation.
     *
     * @return array<class-string>
     */
    public function getPipes(): array;

}
