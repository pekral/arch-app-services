<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

use Illuminate\Pipeline\Pipeline;

/**
 * Abstract base class for data transformation builders.
 *
 * Provides common functionality for building data using Laravel Pipeline.
 *
 * @template TData of array
 * @implements \Pekral\Arch\Service\DataBuilder<TData>
 */
abstract class BaseDataBuilder implements DataBuilder
{

    /**
     * Get the pipes that should be used for data transformation.
     * Override this method to define specific pipes for your builder.
     *
     * @return array<class-string>
     */
    abstract public function getPipes(): array;

    public function __construct(protected readonly Pipeline $pipeline)
    {
    }
    
    /**
     * Transform data using configured pipeline.
     *
     * @param TData $data
     * @return TData
     */
    public function build(array $data): array
    {
        /** @phpstan-var TData $result */
        $result = $this->pipeline
            ->send($data)
            ->through($this->getPipes())
            ->thenReturn();
            
        return $result;
    }

}
