<?php

declare(strict_types = 1);

namespace Pekral\Arch\Console\Commands;

use Illuminate\Console\GeneratorCommand;

final class MakeArchValidationRulesCommand extends GeneratorCommand
{

    /**
     * @var string
     */
    protected $signature = 'make:arch-validation-rules {name : The name of the validation rules class}';

    /**
     * @var string
     */
    protected $description = 'Create a new Arch validation rules class';

    /**
     * @var string
     */
    protected $type = 'ValidationRules';

    protected function getStub(): string
    {
        return __DIR__ . '/../../stubs/validation-rules.stub';
    }

    protected function getDefaultNamespace(mixed $rootNamespace): string
    {
        return $rootNamespace . '\Rules';
    }

}
