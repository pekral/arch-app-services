<?php

declare(strict_types = 1);

namespace Pekral\Arch;

use Illuminate\Support\ServiceProvider;

final class ArchServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/arch.php', 'arch');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/arch.php' => config_path('arch.php'),
            ], 'arch-config');
        }
    }

}
