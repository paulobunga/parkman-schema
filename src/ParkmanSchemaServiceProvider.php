<?php

namespace Paulobunga\ParkmanSchema;

use Illuminate\Support\ServiceProvider;
use Paulobunga\ParkmanSchema\Commands\GenerateModelsCommand;
use Paulobunga\ParkmanSchema\Commands\GenerateMigrationsCommand;

class ParkmanSchemaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'parkman-schema');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'parkman-schema');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('parkman-schema.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/vendor/parkman-schema'),
            ], 'stubs');

            $this->commands([
                GenerateModelsCommand::class,
                GenerateMigrationsCommand::class,
            ]);

            

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/parkman-schema'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/parkman-schema'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/parkman-schema'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'parkman-schema');

        // Register the main class to use with the facade
        $this->app->singleton('parkman-schema', function () {
            return new ParkmanSchema;
        });
    }
}
