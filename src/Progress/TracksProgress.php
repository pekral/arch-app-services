<?php

declare(strict_types = 1);

namespace Pekral\Arch\Progress;

trait TracksProgress
{

    /**
     * @template T
     * @param iterable<T> $items
     * @param callable(T): void $processor
     */
    protected function processBatch(iterable $items, callable $processor, ?ProgressTracker $tracker = null): void
    {
        $itemsArray = is_array($items) ? $items : iterator_to_array($items);
        $total = count($itemsArray);

        $tracker?->start($total);

        foreach ($itemsArray as $item) {
            $processor($item);
            $tracker?->advance();
        }

        $tracker?->finish();
    }

}
