<?php

namespace Musonza\Chat\Tests\Feature;

use Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Participation;
use Musonza\Chat\Tests\Helpers\Models\Client;
use Musonza\Chat\Tests\Helpers\Models\User;
use Musonza\Chat\Tests\TestCase;

class ConversationParticipationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('musonza_chat.should_load_routes', true);
    }

    public function test_store()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();
        $payload      = [
            'participants' => [
                ['id' => $userModel->getKey(), 'type' => $userModel->getMorphClass()],
                ['id' => $clientModel->getKey(), 'type' => $clientModel->getMorphClass()],
            ],
        ];

        $this->postJson(route('conversations.participation.store', [$conversation->getKey()]), $payload)
            ->assertStatus(200);

        $this->assertCount(2, $conversation->participants);
    }

    public function test_index()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);

        $this->getJson(route('conversations.participation.index', [$conversation->getKey()]))
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_show()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        Chat::conversation($conversation)->addParticipants([$userModel]);

        /** @var Participation $participant */
        $participant = $conversation->participants->first();

        $this->getJson(route('conversations.participation.show', [$conversation->getKey(), $participant->getKey()]))
            ->assertStatus(200)
            ->assertJson([
                'messageable_type' => $userModel->getMorphClass(),
            ]);
    }

    public function test_destroy()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);

        $this->assertCount(2, $conversation->participants);

        /** @var Participation $participant */
        $participant = $conversation->participants->first();

        $this->deleteJson(route('conversations.participation.destroy', [$conversation->getKey(), $participant->getKey()]))
            ->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_update()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);

        $this->assertCount(2, $conversation->participants);

        /** @var Participation $participant */
        $participant = $conversation->participants->first();

        $payload = [
            'settings' => [
                'mute_mentions' => true,
            ],
        ];

        $this->putJson(
            route('conversations.participation.update', [$conversation->getKey(), $participant->getKey()]),
            $payload
        )
            ->assertStatus(200)
            ->assertJson(['settings' => $payload['settings']]);
    }
}
