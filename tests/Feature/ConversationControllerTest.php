<?php

namespace Musonza\Chat\Tests\Feature\Conversation;

use Chat;
use Musonza\Chat\ConfigurationManager;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Tests\Helpers\Models\Bot;
use Musonza\Chat\Tests\Helpers\Models\Client;
use Musonza\Chat\Tests\Helpers\Models\User;
use Musonza\Chat\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ConversationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('musonza_chat.should_load_routes', true);
    }

    public function test_store()
    {
        $this->withoutExceptionHandling();

        /** @var User $userModel */
        $userModel   = factory(User::class)->create();
        $clientModel = factory(Client::class)->create();
        $botModel    = factory(Bot::class)->create();

        $participants = [
            ['id' => $userModel->getKey(), 'type' => $userModel->getMorphClass()],
            ['id' => $clientModel->getKey(), 'type' => $clientModel->getMorphClass()],
            ['id' => $botModel->getKey(), 'type' => $botModel->getMorphClass()],
        ];

        $payload = [
            'participants' => $participants,
            'data'         => ['title' => 'PHP Channel', 'description' => 'This is our test channel'],
        ];

        $this->postJson(route('conversations.store'), $payload)
            ->assertStatus(200)
            ->assertJson([
                'data' => $payload['data'],
            ]);

        $this->assertDatabaseHas(ConfigurationManager::PARTICIPATION_TABLE, [
            'messageable_id'   => $userModel->getKey(),
            'messageable_type' => $userModel->getMorphClass(),
        ]);

        $this->assertDatabaseHas(ConfigurationManager::PARTICIPATION_TABLE, [
            'messageable_id'   => $botModel->getKey(),
            'messageable_type' => $botModel->getMorphClass(),
        ]);
    }

    public function test_show()
    {
        $conversation = factory(Conversation::class)->create();

        $this->getJson(route('conversations.show', $conversation->getKey()))
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_update()
    {
        $conversation = factory(Conversation::class)->create();

        $payload = ['data' => ['title' => 'New Title']];

        $this->putJson(route('conversations.update', $conversation->getKey()), $payload)
            ->assertStatus(200)
            ->assertJson([
                'data' => $payload['data'],
            ]);
    }

    public function test_destroy()
    {
        $conversation = factory(Conversation::class)->create();

        $this->deleteJson(route('conversations.destroy', $conversation->getKey()))
            ->assertStatus(200);
    }

    public function test_destroy_with_participants()
    {
        $conversation = factory(Conversation::class)->create();

        Chat::conversation($conversation)->addParticipants([factory(User::class)->create()]);

        $this->deleteJson(route('conversations.destroy', $conversation->getKey()))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
