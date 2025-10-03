<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

use Closure;
use Illuminate\Pipeline\Pipeline;

final readonly class DataBuilder
{

    public function __construct(private Pipeline $pipeline)
    {
    }
    
    /**
     * Transform data using configured pipeline.
     *
     * @param array<string, mixed> $data
     * @param array<class-string> $pipes
     * @return array<string, mixed>
     */
    public function build(array $data, array $pipes, ?Closure $finallyClosure = null): array
    {
        $pipeline = $this->pipeline
            ->send($data)
            ->through($pipes);

        if ($finallyClosure !== null) {
            $pipeline = $pipeline->finally($finallyClosure);
        }

        /** @var array<string, mixed> $result */
        $result = $pipeline->thenReturn();
            
        return $result;
    }

}
