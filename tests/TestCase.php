<?php

namespace Musonza\Chat\Tests;

use Musonza\Chat\User;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'testbench']);
        $this->loadMigrationsFrom(__DIR__.'/../src/migrations');
        $this->withFactories(__DIR__.'/../src/database/factories');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // $app['config']->set('database.default', 'testbench');
        // $app['config']->set('database.connections.testbench', [
        //     'driver' => 'mysql',
        //     'database' => 'chat',
        //     'username' => 'root',
        //     'host' => '127.0.0.1',
        //     'password' => 'my-secret-pw',
        //     'prefix' => '',
        // ]);

        $app['config']->set('musonza_chat.user_model', 'Musonza\Chat\User');
        $app['config']->set('musonza_chat.laravel_notifications', false);
        $app['config']->set('musonza_chat.broadcasts', false);
    }

    protected function getPackageProviders($app)
    {
        return [
            \Orchestra\Database\ConsoleServiceProvider::class,
            \Musonza\Chat\ChatServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Chat' => \Musonza\Chat\Facades\ChatFacade::class,
        ];
    }

    public function createUsers($count = 1)
    {
        return factory(User::class, $count)->create();
    }
}
