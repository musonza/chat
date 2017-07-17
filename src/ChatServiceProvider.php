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

    public function boot()
    {
        $this->registerAssets();
    }

    public function register()
    {
        $this->registerChat();
    }

    private function registerChat()
    {
        $this->app->bind('chat', function () {
            return $this->app->make('Musonza\Chat\Chat');
        });
    }

    public function registerAssets()
    {
        $this->publishes([
            __DIR__.'/migrations' => database_path('/migrations'),
            __DIR__.'/config'     => config_path(),
        ]);
    }
}
