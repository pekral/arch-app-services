<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Progress;

use ArrayObject;
use Pekral\Arch\Progress\ProgressTracker;
use Pekral\Arch\Progress\TracksProgress;

test('processBatch processes all items without tracker', function (): void {
    $processor = new TestClassWithTracksProgress();
    $items = ['a', 'b', 'c'];
    /** @var \ArrayObject<int, string> $processed */
    $processed = new ArrayObject();

    $processor->runBatch($items, function (string $item) use ($processed): void {
        $processed->append($item);
    });

    expect($processed->getArrayCopy())->toBe(['a', 'b', 'c']);
});

test('processBatch calls tracker start with correct total', function (): void {
    $processor = new TestClassWithTracksProgress();
    $tracker = new TestProgressTracker();
    $items = ['a', 'b', 'c', 'd'];

    $processor->runBatch($items, function (): void {
    }, $tracker);

    expect($tracker->getStartedWith())->toBe(4);
});

test('processBatch calls tracker advance for each item', function (): void {
    $processor = new TestClassWithTracksProgress();
    $tracker = new TestProgressTracker();
    $items = ['a', 'b', 'c'];

    $processor->runBatch($items, function (): void {
    }, $tracker);

    expect($tracker->getAdvanceCount())->toBe(3);
});

test('processBatch calls tracker finish after processing', function (): void {
    $processor = new TestClassWithTracksProgress();
    $tracker = new TestProgressTracker();
    $items = ['a', 'b'];

    $processor->runBatch($items, function (): void {
    }, $tracker);

    expect($tracker->isFinished())->toBeTrue();
});

test('processBatch handles empty items array', function (): void {
    $processor = new TestClassWithTracksProgress();
    $tracker = new TestProgressTracker();
    /** @var array<string> $items */
    $items = [];

    $processor->runBatch($items, function (): void {
    }, $tracker);

    expect($tracker->getStartedWith())->toBe(0)
        ->and($tracker->getAdvanceCount())->toBe(0)
        ->and($tracker->isFinished())->toBeTrue();
});

test('processBatch handles iterable generator', function (): void {
    $processor = new TestClassWithTracksProgress();
    $tracker = new TestProgressTracker();
    $generator = (function () {
        yield 'x';
        yield 'y';
        yield 'z';
    })();
    /** @var \ArrayObject<int, string> $processed */
    $processed = new ArrayObject();

    $processor->runBatch($generator, function (string $item) use ($processed): void {
        $processed->append($item);
    }, $tracker);

    expect($processed->getArrayCopy())->toBe(['x', 'y', 'z'])
        ->and($tracker->getStartedWith())->toBe(3)
        ->and($tracker->getAdvanceCount())->toBe(3);
});

test('processBatch works without tracker for generator', function (): void {
    $processor = new TestClassWithTracksProgress();
    $generator = (function () {
        yield 1;
        yield 2;
    })();
    /** @var \ArrayObject<int, int> $processed */
    $processed = new ArrayObject();

    $processor->runBatch($generator, function (int $item) use ($processed): void {
        $processed->append($item);
    });

    expect($processed->getArrayCopy())->toBe([1, 2]);
});

final class TestClassWithTracksProgress
{

    use TracksProgress;

    /**
     * @template T
     * @param iterable<T> $items
     * @param callable(T): void $processor
     */
    public function runBatch(iterable $items, callable $processor, ?ProgressTracker $tracker = null): void
    {
        $this->processBatch($items, $processor, $tracker);
    }

}

final class TestProgressTracker implements ProgressTracker
{

    private ?int $startedWith = null;

    private int $advanceCount = 0;

    private bool $finished = false;

    public function start(int $total): void
    {
        $this->startedWith = $total;
    }

    public function advance(int $step = 1): void
    {
        $this->advanceCount += $step;
    }

    public function finish(): void
    {
        $this->finished = true;
    }

    public function getStartedWith(): ?int
    {
        return $this->startedWith;
    }

    public function getAdvanceCount(): int
    {
        return $this->advanceCount;
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }

}
