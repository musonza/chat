<?php

namespace Musonza\Chat;

use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishMigrations();
        $this->publishConfig();

        if (config('musonza_chat.should_load_routes')) {
            require __DIR__.'/Http/routes.php';
        }
    }

    /**
     * Register application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerChat();
    }

    /**
     * Registers Chat.
     *
     * @return void
     */
    private function registerChat()
    {
        $this->app->bind('\Musonza\Chat\Chat', function () {
            return $this->app->make(Chat::class);
        });
    }

    /**
     * Publish package's migrations.
     *
     * @return void
     */
    public function publishMigrations()
    {
        $timestamp = date('Y_m_d_His', time());
        $stub = __DIR__.'/../database/migrations/create_chat_tables.php';
        $target = $this->app->databasePath().'/migrations/'.$timestamp.'_create_chat_tables.php';

        $this->publishes([$stub => $target], 'chat.migrations');
    }

    /**
     * Publish package's config file.
     *
     * @return void
     */
    public function publishConfig()
    {
        $this->publishes([
            __DIR__.'/../config' => config_path(),
        ], 'chat.config');
    }
}
