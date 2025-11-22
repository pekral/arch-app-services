<?php

declare(strict_types = 1);

namespace Pekral\Arch\DataBuilder;

use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

use function app;
use function collect;
use function is_int;
use function is_string;

/**
 * Trait for building and transforming data through pipeline processing.
 *
 * Provides functionality to process data arrays through a series of pipeline classes
 * that can transform, validate, or modify the data. Supports both general pipelines
 * (applied to all data) and specific pipelines (applied to specific keys).
 *
 * Usage:
 * $data = $this->build($input, [
 *     'email' => LowercaseEmailPipe::class,
 *     'name' => UcFirstNamePipe::class,
 *     '*' => TrimAllFieldsPipe::class, // Applied to all fields
 * ]);
 *
 * Pipeline classes must implement the pipeline contract and receive the data
 * as input, returning the transformed data.
 */
trait DataBuilder
{

    /**
     * Build and transform data through pipeline processing.
     *
     * Processes data through a series of pipeline classes. General pipelines (integer keys or '*')
     * are applied first to all data, then specific pipelines (string keys) are applied to
     * individual fields.
     *
     * @param mixed $data Input data to process (typically an array)
     * @param array<string|int, class-string> $pipelines Map of field names to pipeline class names, or integer keys for general pipelines
     * @return array<string, mixed> Transformed data
     * @throws \InvalidArgumentException When mixing string and integer keys in pipelines
     */
    public function build(mixed $data, array $pipelines = []): array
    {
        $this->validateDefinedPipes($pipelines);

        $collection = collect($pipelines);
        $generalKeys = $collection->keys()->filter(
            static fn (string|int $key): bool => is_int($key) || $key === '*',
        )->all();
        $specificKeys = $collection->keys()->filter(
            static fn (string|int $key): bool => !is_int($key) && $key !== '*',
        )->all();

        $processedData = $this->processPipelines(
            $data,
            $collection->only($generalKeys)->values()->all(),
        );

        return $this->processPipelines(
            $processedData,
            $collection->only($specificKeys)->values()->all(),
        );
    }

    /**
     * Validate that pipeline keys are consistent (either all string or all integer).
     *
     * @param array<string|int, class-string> $pipelines Pipeline configuration to validate
     * @throws \InvalidArgumentException When mixing string and integer keys
     */
    private function validateDefinedPipes(array $pipelines): void
    {
        $keys = collect($pipelines)->keys();
        $hasStringKeys = $keys->contains(static fn (string|int $key): bool => is_string($key));
        $hasIntKeys = $keys->contains(static fn (string|int $key): bool => is_int($key));

        if ($hasIntKeys && $hasStringKeys) {
            throw new InvalidArgumentException('Pipes keys must be either string or integer');
        }
    }

    /**
     * Process data through a series of pipeline classes.
     *
     * @param mixed $data Input data to process
     * @param array<int, class-string> $pipes Array of pipeline class names to apply
     * @return array<string, mixed> Processed data
     */
    private function processPipelines(mixed $data, array $pipes): array
    {
        /** @var array<string, mixed> $result */
        $result = app(Pipeline::class)
            ->send($data)
            ->through($pipes)
            ->thenReturn();

        return $result;
    }

}
