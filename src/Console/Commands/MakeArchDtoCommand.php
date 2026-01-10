<?php

declare(strict_types = 1);

namespace Pekral\Arch\Console\Commands;

use Illuminate\Console\GeneratorCommand;

final class MakeArchDtoCommand extends GeneratorCommand
{

    /**
     * @var string
     */
    protected $signature = 'make:arch-dto {name : The name of the DTO class}';

    /**
     * @var string
     */
    protected $description = 'Create a new Arch DTO class';

    /**
     * @var string
     */
    protected $type = 'DTO';

    protected function getStub(): string
    {
        return __DIR__ . '/../../stubs/dto.stub';
    }

    protected function getDefaultNamespace(mixed $rootNamespace): string
    {
        return $rootNamespace . '\DTO';
    }

}
