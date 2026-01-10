<?php

declare(strict_types = 1);

namespace Pekral\Arch\Progress;

interface ProgressTracker
{

    public function start(int $total): void;

    public function advance(int $step = 1): void;

    public function finish(): void;

}
