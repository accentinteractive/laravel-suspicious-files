<?php

namespace Accentinteractive\LaravelSuspiciousFiles;

use Accentinteractive\LaravelSuspiciousFiles\Commands\SuspiciousFilesFind;
use Illuminate\Support\ServiceProvider;

class LaravelSuspiciousFilesServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/suspicious-files.php' => config_path('suspicious-files.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'suspicious');

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-suspicious-files'),
        ], 'lang');*/
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/suspicious-files.php', 'suspicious-files');

        $this->app->bind('command.suspicious-files:find', SuspiciousFilesFind::class);

        $this->commands([
            'command.suspicious-files:find',
        ]);
    }
}
