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
    public function testStore()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel = factory(User::class)->create();
        $clientModel = factory(Client::class)->create();
        $payload = [
            'participants' => [
                ['id' => $userModel->getKey(), 'type' => get_class($userModel)],
                ['id' => $clientModel->getKey(), 'type' => get_class($clientModel)],
            ],
        ];

        $this->postJson(route('conversations.participation.store', [$conversation->getKey()]), $payload)
            ->assertStatus(200);

        $this->assertCount(2, $conversation->participants);
    }

    public function testDestroy()
    {
        $this->withoutExceptionHandling();
        $conversation = factory(Conversation::class)->create();
        $userModel = factory(User::class)->create();
        $clientModel = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);

        $this->assertCount(2, $conversation->participants);

        /** @var Participation $participant */
        $participant = $conversation->participants->first();

        $this->deleteJson(route('conversations.participation.destroy', [$conversation->getKey(), $participant->getKey()]))
            ->assertStatus(200)
            ->assertJsonCount(1);
    }
}
