<?php

namespace Thorazine\Geo;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;

class GeoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Kernel $kernel, Router $router)
    {
        // publish 
        $this->publishes([
            __DIR__.'/config/geo.php' => config_path('geo.php'),
        ], 'geo');

        // run migrations when needed
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->loadTranslationsFrom(__DIR__ . '/lang', 'geo');

        // Register console commands
        if($this->app->runningInConsole()) 
        {
            $this->commands([
                Console\Commands\GeoAll::class,
                Console\Commands\ImportCities::class,
                Console\Commands\SyncTags::class,
                Console\Commands\LanguagePrint::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/geo.php', 'geo');
    }
}
