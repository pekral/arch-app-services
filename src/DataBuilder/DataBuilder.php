<?php

declare(strict_types = 1);

namespace Pekral\Arch\DataBuilder;

use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

use function app;
use function collect;
use function is_int;
use function is_string;

trait DataBuilder
{

    /**
     * @param array<string|int, class-string> $pipelines
     * @return array<string, mixed>
     */
    public function build(mixed $data, array $pipelines = []): array
    {
        $this->validateDefinedPipes($pipelines);

        $collection = collect($pipelines);
        /** @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter */
        $generalPipes = $collection->filter(
            static fn (string $pipe, string|int $key): bool => is_int($key) || $key === '*',
        )->values()->all();
        /** @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter */
        $specificPipes = $collection->filter(
            static fn (string $pipe, string|int $key): bool => is_string($key) && $key !== '*',
        )->values()->all();

        return $this->processPipelines(
            $this->processPipelines($data, $generalPipes),
            $specificPipes,
        );
    }

    /**
     * @param array<string|int, class-string> $pipelines
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
     * @param array<int, class-string> $pipes
     * @return array<string, mixed>
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
