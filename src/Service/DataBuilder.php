<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

use Illuminate\Pipeline\Pipeline;
use Pekral\Arch\Data\ActionData;

final readonly class DataBuilder
{

    public function __construct(private Pipeline $pipeline)
    {
    }
    
    /**
     * Transform data using configured pipeline.
     *
     * @param array<class-string> $pipes
     * @return array<string, mixed>
     */
    public function build(ActionData $data, array $pipes): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->pipeline
            ->send($data->getData())
            ->through($pipes)
            ->thenReturn();
            
        return $result;
    }

}
