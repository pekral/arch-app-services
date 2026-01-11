<?php

declare(strict_types = 1);

namespace Pekral\Arch;

use Illuminate\Support\ServiceProvider;
use Pekral\Arch\Console\Commands\MakeArchActionCommand;
use Pekral\Arch\Console\Commands\MakeArchDtoCommand;
use Pekral\Arch\Console\Commands\MakeArchServiceCommand;
use Pekral\Arch\Console\Commands\MakeArchValidationRulesCommand;

final class ArchServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/arch.php', 'arch');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/arch.php' => config_path('arch.php'),
            ], 'arch-config');

            $this->publishes([
                __DIR__ . '/stubs' => base_path('stubs/arch'),
            ], 'arch-stubs');

            $this->commands([
                MakeArchActionCommand::class,
                MakeArchDtoCommand::class,
                MakeArchServiceCommand::class,
                MakeArchValidationRulesCommand::class,
            ]);
        }
    }

}
