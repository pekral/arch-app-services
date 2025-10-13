<?php

declare(strict_types = 1);

namespace Pekral\Arch\DataBuilder;

use Illuminate\Pipeline\Pipeline;

use function app;
use function collect;
use function is_int;
use function is_string;

/**
 * Service for data transformation using Laravel Pipeline pattern.
 * Allows applying a series of transformations to data sequentially through defined pipes.
 */
trait DataBuilder
{

    /**
     * Transforms data using defined pipes via Laravel Pipeline.
     *
     * @param mixed $data Input data to transform
     * @param array<string|int, class-string> $pipelines Array of pipe classes with keys
     * @return array<string, mixed> Transformed data
     */
    public function build(mixed $data, array $pipelines = []): array
    {
        $this->validateDefinedPipes($pipelines);

        $generalPipes = [];
        $specificPipes = [];

        foreach ($pipelines as $key => $pipeClass) {
            if (is_int($key) || $key === '*') {
                $generalPipes[] = $pipeClass;
            } else {
                $specificPipes[] = $pipeClass;
            }
        }

        $processedData = $this->processPipelines($data, $generalPipes);

        return $this->processPipelines($processedData, $specificPipes);
    }

    /**
     * @param array<string|int, class-string> $pipelines
     */
    private function validateDefinedPipes(array $pipelines): void
    {
        $countStringSpecificPipes = collect($pipelines)->keys()->filter(static fn (string|int $key): bool => is_string($key))->count();
        $countIntSpecificPipes = collect($pipelines)->keys()->filter(static fn (string|int $key): bool => is_int($key))->count();

        if ($countIntSpecificPipes > 0 && $countStringSpecificPipes > 0) {
            throw new \InvalidArgumentException('Pipes keys must be either string or integer');
        }
    }

    /**
     * @param array<class-string> $pipes
     * @return array<string, mixed>
     */
    private function processPipelines(mixed $data, array $pipes): array
    {
        $pipeline = app(Pipeline::class);
        /** @var array<string, mixed> $result */
        $result = $pipeline->send($data)
            ->through($pipes)
            ->thenReturn();

        return $result;
    }

}
