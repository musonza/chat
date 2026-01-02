<?php

namespace Musonza\Chat\Tests;

require __DIR__ . '/../database/migrations/create_chat_tables.php';
require __DIR__ . '/../database/migrations/add_is_encrypted_to_messages_table.php';
require __DIR__ . '/Helpers/migrations.php';

use AddIsEncryptedToMessagesTable;
use CreateChatTables;
use CreateTestTables;
use Illuminate\Foundation\Application;
use Musonza\Chat\ChatServiceProvider;
use Musonza\Chat\Facades\ChatFacade;
use Musonza\Chat\Tests\Helpers\Models\User;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $conversation;

    protected $prefix = 'chat_';

    protected $userModelPrimaryKey;

    public $users;

    /** @var User */
    protected $alpha;

    /** @var User */
    protected $bravo;

    /** @var User */
    protected $charlie;

    /** @var User */
    protected $delta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->users                                               = $this->createUsers(6);
        [$this->alpha, $this->bravo, $this->charlie, $this->delta] = $this->users;
    }

    /**
     * Set up the database schema and factories.
     */
    protected function setUpDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Helpers/database/migrations');

        $config = config('musonza_chat');
        if (isset($config['user_model'])) {
            $userModel                 = app($config['user_model']);
            $this->userModelPrimaryKey = $userModel->getKeyName();
        }

        (new CreateChatTables)->up();
        (new AddIsEncryptedToMessagesTable)->up();
        (new CreateTestTables)->up();

        $this->withFactories(__DIR__ . '/Helpers/factories');
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Set app key for encryption (required by web middleware)
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('musonza_chat.user_model', 'Musonza\Chat\Tests\Helpers\Models\User');
        $app['config']->set('musonza_chat.sent_message_event', 'Musonza\Chat\Eventing\MessageWasSent');
        $app['config']->set('musonza_chat.broadcasts', false);
        $app['config']->set('musonza_chat.user_model_primary_key', null);
        $app['config']->set('musonza_chat.routes.enabled', true);
        $app['config']->set('musonza_chat.should_load_routes', true);
    }

    protected function getPackageProviders($app)
    {
        return [
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

    protected function tearDown(): void
    {
        (new AddIsEncryptedToMessagesTable)->down();
        (new CreateChatTables)->down();
        (new CreateTestTables)->down();
        parent::tearDown();
    }
}
