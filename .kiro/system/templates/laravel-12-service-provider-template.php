<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class {{ServiceProviderName}} extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     */
    public array $bindings = [
        // Interface => Implementation
    ];

    /**
     * All of the container singletons that should be registered.
     */
    public array $singletons = [
        // Interface => Implementation
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register service bindings
        $this->app->bind({{ServiceInterface}}::class, {{ServiceImplementation}}::class);
        
        // Register singleton services
        $this->app->singleton({{SingletonInterface}}::class, function (Application $app) {
            return new {{SingletonImplementation}}(
                $app->make({{Dependency}}::class)
            );
        });

        // Register configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/{{config_name}}.php',
            '{{config_name}}'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/{{config_name}}.php' => config_path('{{config_name}}.php'),
            ], '{{config_name}}-config');
        }

        // Register event listeners
        // Event::listen({{EventClass}}::class, {{ListenerClass}}::class);

        // Register middleware
        // $this->app['router']->aliasMiddleware('{{middleware_name}}', {{MiddlewareClass}}::class);

        // Register view composers
        // View::composer('{{view_pattern}}', {{ComposerClass}}::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            {{ServiceInterface}}::class,
            {{SingletonInterface}}::class,
        ];
    }
}