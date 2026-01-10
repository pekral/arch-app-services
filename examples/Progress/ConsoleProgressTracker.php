<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Progress;

use Illuminate\Console\Command;
use Pekral\Arch\Progress\ProgressTracker;
use Symfony\Component\Console\Helper\ProgressBar;

final class ConsoleProgressTracker implements ProgressTracker
{

    private ?ProgressBar $progressBar = null;

    public function __construct(private readonly Command $command)
    {
    }

    public function start(int $total): void
    {
        $this->progressBar = $this->command->getOutput()->createProgressBar($total);
        $this->progressBar->start();
    }

    public function advance(int $step = 1): void
    {
        $this->progressBar?->advance($step);
    }

    public function finish(): void
    {
        $this->progressBar?->finish();
        $this->command->newLine();
    }

}
