<?php

namespace Hozien\Uploader;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Hozien\Uploader\Models\Upload;
use Hozien\Uploader\Policies\UploadPolicy;
use function resource_path;
use function app;

class UploaderServiceProvider extends ServiceProvider
{
    /**
     * Package version.
     */
    public const VERSION = '1.0.2';

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
        $this->registerBladeComponents();
        $this->registerPolicies();
        $this->registerCommands();
        $this->registerMiddleware();
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

        // Register version information
        $this->app->singleton('uploader.version', function () {
            return self::VERSION;
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
     * Register Blade components.
     *
     * @return void
     */
    protected function registerBladeComponents()
    {
        // Register anonymous components in the 'uploader' namespace for both package and published paths
        Blade::anonymousComponentNamespace(__DIR__ . '/resources/views/components', 'uploader');

        // Optionally, if users publish the views:
        Blade::anonymousComponentNamespace(
            resource_path('views/vendor/uploader/components'),
            'uploader'
        );

        // Register a Blade component alias for dot syntax usage
        Blade::component('uploader::popup', 'uploader.popup');
    }

    /**
     * Register policies.
     *
     * @return void
     */
    protected function registerPolicies()
    {
        Gate::policy(Upload::class, UploadPolicy::class);
    }

    /**
     * Register package commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Hozien\Uploader\Console\Commands\CleanupOrphanedFilesCommand::class,
                \Hozien\Uploader\Console\Commands\GenerateThumbnailsCommand::class,
                \Hozien\Uploader\Console\Commands\UploaderStatusCommand::class,
            ]);
        }
    }

    /**
     * Register package middleware.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        $this->app['router']->aliasMiddleware('json', \Hozien\Uploader\Http\Middleware\EnsureJsonResponse::class);
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            // Configuration
            $this->publishes([
                __DIR__ . '/config/uploader.php' => $this->app->configPath('uploader.php'),
            ], ['uploader-config', 'uploader']);

            // Views
            $this->publishes([
                __DIR__ . '/resources/views' => $this->app->resourcePath('views/vendor/uploader'),
            ], ['uploader-views', 'uploader']);

            // Frontend Assets
            $this->publishes([
                __DIR__ . '/resources/js' => $this->app->publicPath('vendor/uploader'),
            ], ['uploader-assets', 'uploader']);

            // Language Files
            $this->publishes([
                __DIR__ . '/resources/lang' => $this->app->langPath('vendor/uploader'),
            ], ['uploader-lang', 'uploader']);

            // Migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
            ], ['uploader-migrations', 'uploader']);

            // Policies
            $this->publishes([
                __DIR__ . '/Policies/UploadPolicy.php' => $this->app->basePath('app/Policies/UploadPolicy.php'),
            ], ['uploader-policy', 'uploader']);

            // Tests (for development)
            $this->publishes([
                __DIR__ . '/../tests' => base_path('tests/vendor/uploader'),
            ], ['uploader-tests']);

            // Publish everything at once
            $this->publishes([
                __DIR__ . '/config/uploader.php' => $this->app->configPath('uploader.php'),
                __DIR__ . '/resources/views' => $this->app->resourcePath('views/vendor/uploader'),
                __DIR__ . '/resources/js' => $this->app->publicPath('vendor/uploader'),
                __DIR__ . '/resources/lang' => $this->app->langPath('vendor/uploader'),
                __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'uploader');
        }
    }

    /**
     * Get the package version.
     *
     * @return string
     */
    public static function version(): string
    {
        return self::VERSION;
    }
}
