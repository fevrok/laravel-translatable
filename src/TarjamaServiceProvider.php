<?php

namespace LaravelArab\Tarjama;

use Illuminate\Support\ServiceProvider;

class TarjamaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../publishable/database/migrations');
        $this->publishes([
            __DIR__ . '/../publishable/config/tarjama.php' => config_path('tarjama.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../publishable/config/tarjama.php',
            'tarjama'
        );
    }
}
