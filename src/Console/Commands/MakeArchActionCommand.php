<?php

declare(strict_types = 1);

namespace Pekral\Arch\Console\Commands;

use Illuminate\Console\GeneratorCommand;

final class MakeArchActionCommand extends GeneratorCommand
{

    /**
     * @var string
     */
    protected $signature = 'make:arch-action {name : The name of the action class}';

    /**
     * @var string
     */
    protected $description = 'Create a new Arch action class';

    /**
     * @var string
     */
    protected $type = 'Action';

    protected function getStub(): string
    {
        return __DIR__ . '/../../stubs/action.stub';
    }

    protected function getDefaultNamespace(mixed $rootNamespace): string
    {
        return $rootNamespace . '\Actions';
    }

}
