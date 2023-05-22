<?php

namespace Nh1816\Loga;

// use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Loga;

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

        // Register console commands
        if($this->app->runningInConsole()) 
        {
            $this->commands([
                Console\Commands\GeoAll::class,
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
