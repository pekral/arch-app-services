<?php

declare(strict_types = 1);

namespace Pekral\Arch\DataBuilder;

use Illuminate\Pipeline\Pipeline;

/**
 * Service for data transformation using Laravel Pipeline pattern.
 * Allows applying a series of transformations to data sequentially through defined pipes.
 */
final readonly class DataBuilder
{

    public function __construct(private Pipeline $pipeline)
    {
    }
    
    /**
     * Transforms data using defined pipes via Laravel Pipeline.
     *
     * @param mixed $data Input data to transform
     * @param array<class-string> $pipes Array of pipe classes implementing handle method
     * @return array<string, mixed> Transformed data
     */
    public function build(mixed $data, array $pipes): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->pipeline
            ->send($data)
            ->through($pipes)
            ->thenReturn();

        return $result;
    }

}
