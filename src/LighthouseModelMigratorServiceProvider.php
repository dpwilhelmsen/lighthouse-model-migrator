<?php

namespace DanielWilhelmsen\LighthouseModelMigrator;

use Illuminate\Support\ServiceProvider;
use DanielWilhelmsen\LighthouseModelMigrator\Console\LighthouseMigrateModel;

class LighthouseModelMigratorServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'danielwilhelmsen');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'danielwilhelmsen');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        /*$this->mergeConfigFrom(__DIR__.'/../config/lighthousemodelmigrator.php', 'lighthousemodelmigrator');

        // Register the service the package provides.
        $this->app->singleton('lighthousemodelmigrator', function ($app) {
            return new LighthouseModelMigrator;
        });*/
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        /*$this->publishes([
            __DIR__.'/../config/lighthousemodelmigrator.php' => config_path('lighthousemodelmigrator.php'),
        ], 'lighthousemodelmigrator.config');*/

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/danielwilhelmsen'),
        ], 'lighthousemodelmigrator.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/danielwilhelmsen'),
        ], 'lighthousemodelmigrator.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/danielwilhelmsen'),
        ], 'lighthousemodelmigrator.views');*/

        // Registering package commands.
        $this->commands([
            LighthouseMigrateModel::class,
        ]);
    }
}
