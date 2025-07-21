<?php

namespace Hozien\Uploader;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use function resource_path;
use function app;

class UploaderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerPublishing();
        // Register anonymous components in the 'uploader' namespace for both package and published paths
        Blade::anonymousComponentNamespace(__DIR__ . '/resources/views/components', 'uploader');

        // Optionally, if users publish the views:
        Blade::anonymousComponentNamespace(
            resource_path('views/vendor/uploader/components'),
            'uploader'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/uploader.php', 'uploader');

        $this->app->singleton('uploader', function ($app) {
            return new \Hozien\Uploader\Uploader();
        });
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/uploader.php');
    }

    /**
     * Register the package views.
     *
     * @return void
     */
    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'uploader');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/uploader.php' => $this->app->configPath('uploader.php'),
            ], 'uploader-config');

            $this->publishes([
                __DIR__ . '/resources/views' => $this->app->resourcePath('views/vendor/uploader'),
            ], 'uploader-views');

            $this->publishes([
                __DIR__ . '/resources/js' => $this->app->publicPath('vendor/uploader'),
            ], 'uploader-assets');
        }
    }
}
