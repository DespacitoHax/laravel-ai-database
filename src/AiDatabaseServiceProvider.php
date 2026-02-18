<?php

namespace Mostafaarafat\AiDatabase;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Mostafaarafat\AiDatabase\Mixins\DatabaseManagerMixin;

class AiDatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/ai-database.php',
            'ai-database'
        );

        // Register services as singletons
        $this->app->singleton(Services\AiService::class, function ($app) {
            return new Services\AiService();
        });

        $this->app->singleton(Services\SchemaService::class, function ($app) {
            return new Services\SchemaService();
        });

        $this->app->singleton(Services\QueryService::class, function ($app) {
            return new Services\QueryService(
                $app->make(Services\AiService::class),
                $app->make(Services\SchemaService::class)
            );
        });

        $this->app->singleton(Services\AnswerService::class, function ($app) {
            return new Services\AnswerService(
                $app->make(Services\AiService::class)
            );
        });
    }

    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ai-database.php' => config_path('ai-database.php'),
            ], 'ai-database-config');

            // Publish views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/ai-database'),
            ], 'ai-database-views');

            // Register commands
            $this->commands([
                Commands\AskDatabaseCommand::class,
            ]);
        }

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-database');

        // Register DB mixin
        DB::mixin(new DatabaseManagerMixin());
    }
}