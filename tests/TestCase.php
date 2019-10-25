<?php

namespace Musonza\Chat\Tests;

require __DIR__.'/../database/migrations/create_chat_tables.php';
require __DIR__.'/Helpers/migrations.php';

use CreateChatTables;
use CreateTestTables;
use Musonza\Chat\ChatServiceProvider;
use Musonza\Chat\Facades\ChatFacade;
use Musonza\Chat\Tests\Helpers\Models\User;
use Orchestra\Database\ConsoleServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $conversation;
    protected $prefix = 'mc_';
    protected $userModelPrimaryKey;

    public function __construct()
    {
        parent::__construct();
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'testbench']);
        $this->withFactories(__DIR__.'/Helpers/factories');
        $this->migrate();
        $this->users = $this->createUsers(6);
    }

    protected function migrateTestTables()
    {
        $config = config('musonza_chat');
        $userModel = app($config['user_model']);
        $this->userModelPrimaryKey = $userModel->getKeyName();
    }

    protected function migrate()
    {
        $this->migrateTestTables();
        (new CreateChatTables())->up();
        (new CreateTestTables())->up();
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

        $app['config']->set('musonza_chat.user_model', 'Musonza\Chat\Tests\Helpers\Models\User');
        $app['config']->set('musonza_chat.sent_message_event', 'Musonza\Chat\Eventing\MessageWasSent');
        $app['config']->set('musonza_chat.broadcasts', false);
        $app['config']->set('musonza_chat.user_model_primary_key', null);
    }

    protected function getPackageProviders($app)
    {
        return [
            ConsoleServiceProvider::class,
            ChatServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Chat' => ChatFacade::class,
        ];
    }

    public function createUsers($count = 1)
    {
        return factory(User::class, $count)->create();
    }

    public function tearDown(): void
    {
        (new CreateChatTables())->down();
        (new CreateTestTables())->down();
        parent::tearDown();
    }
}
