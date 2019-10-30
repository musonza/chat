<?php

namespace Musonza\Chat\Tests\Feature;

use Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Tests\Helpers\Models\Client;
use Musonza\Chat\Tests\Helpers\Models\User;
use Musonza\Chat\Tests\TestCase;

class ConversationMessageControllerTest extends TestCase
{
    public function testStore()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel = factory(User::class)->create();
        $clientModel = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);

        $payload = [
            'participant_id' => $userModel->getKey(),
            'participant_type' => get_class($userModel),
            'message' => [
                'body' => 'Hello',
            ]
        ];

        $this->postJson(route('conversations.messages.store', $conversation->getKey()), $payload)
            ->assertStatus(200)
            ->assertJsonStructure([
                'sender',
                'conversation',
                'body',
            ]);
    }

    public function testIndex()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel = factory(User::class)->create();
        $clientModel = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);
        Chat::message('hello')->from($userModel)->to($conversation)->send();
        Chat::message('hey')->from($clientModel)->to($conversation)->send();
        Chat::message('ndeipi')->from($userModel)->to($conversation)->send();

        $parameters = [
             $conversation->getKey(),
            'participant_id' => $userModel->getKey(),
            'participant_type' => get_class($userModel),
//            'page' => 1,
//            'perPage' => 2,
//            'sorting' => "desc",
//            'columns' => [
//                '*'
//            ],
        ];

        $this->getJson(route('conversations.messages.index', $parameters))
            ->assertStatus(200)
            ->assertJson([
                'current_page' => 1
            ])
            ->assertJsonStructure(
                [
                    'data' => [[
                        'sender',
                        'body',
                    ]]
                ]
            );
    }
}