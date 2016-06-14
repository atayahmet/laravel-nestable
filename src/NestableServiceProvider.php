<?php

namespace Nestable;

use Illuminate\Support\ServiceProvider;

class NestableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/nestable.php' => config_path('nestable.php'),
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/nestable.php', 'nestable'
        );

        $this->app->bind('nestableservice', 'Nestable\Services\NestableService');
    }
}
