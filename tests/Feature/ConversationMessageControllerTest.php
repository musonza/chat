<?php

namespace Musonza\Chat\Tests\Feature;

use Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message;
use Musonza\Chat\Tests\Helpers\Models\Client;
use Musonza\Chat\Tests\Helpers\Models\User;
use Musonza\Chat\Tests\TestCase;

class ConversationMessageControllerTest extends TestCase
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

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);

        $payload = [
            'participant_id'   => $userModel->getKey(),
            'participant_type' => $userModel->getMorphClass(),
            'message'          => [
                'body' => 'Hello',
            ],
        ];

        $this->postJson(route('conversations.messages.store', $conversation->getKey()), $payload)
            ->assertStatus(200)
            ->assertJsonStructure([
                'sender',
                'conversation',
                'body',
            ]);
    }

    public function test_index()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);
        Chat::message('hello')->from($userModel)->to($conversation)->send();
        Chat::message('hey')->from($clientModel)->to($conversation)->send();
        Chat::message('ndeipi')->from($userModel)->to($conversation)->send();

        $parameters = [
            $conversation->getKey(),
            'participant_id'   => $userModel->getKey(),
            'participant_type' => $userModel->getMorphClass(),
            'page'             => 1,
            'perPage'          => 2,
            'sorting'          => 'desc',
            'columns'          => [
                '*',
            ],
        ];

        $this->getJson(route('conversations.messages.index', $parameters))
            ->assertStatus(200)
            ->assertJson([
                'current_page' => 1,
            ])
            ->assertJsonStructure(
                [
                    'data' => [
                        [
                            'sender',
                            'body',
                        ],
                    ],
                ]
            );
    }

    public function test_clear_conversation()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();

        $parameters = [
            $conversation->getKey(),
            'participant_id'   => $userModel->getKey(),
            'participant_type' => $userModel->getMorphClass(),
        ];

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);
        Chat::message('hello')->from($userModel)->to($conversation)->send();
        Chat::message('hey')->from($clientModel)->to($conversation)->send();
        Chat::message('ndeipi')->from($userModel)->to($conversation)->send();

        $this->deleteJson(route('conversations.messages.destroy.all', $parameters))
            ->assertSuccessful();
    }

    public function test_destroy()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);
        Chat::message('hello')->from($userModel)->to($conversation)->send();
        Chat::message('hey')->from($clientModel)->to($conversation)->send();
        /** @var Message $message */
        $message = Chat::message('hello')->from($userModel)->to($conversation)->send();

        $parameters = [
            $conversation->getKey(),
            $message->getKey(),
            'participant_id'   => $userModel->getKey(),
            'participant_type' => $userModel->getMorphClass(),
        ];

        $this->deleteJson(route('conversations.messages.destroy', $parameters))
            ->assertSuccessful();
        $this->assertCount(2, Chat::conversation($conversation)->setParticipant($userModel)->getMessages());
    }

    public function test_index_with_cursor()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);
        Chat::message('message 1')->from($userModel)->to($conversation)->send();
        Chat::message('message 2')->from($clientModel)->to($conversation)->send();
        Chat::message('message 3')->from($userModel)->to($conversation)->send();

        $parameters = [
            $conversation->getKey(),
            'participant_id'   => $userModel->getKey(),
            'participant_type' => $userModel->getMorphClass(),
            'perPage'          => 2,
            'sorting'          => 'asc',
        ];

        $response = $this->getJson(route('conversations.messages.index.cursor', $parameters))
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'sender',
                        'body',
                    ],
                ],
                'next_cursor',
                'next_page_url',
                'path',
                'per_page',
                'prev_cursor',
                'prev_page_url',
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_with_cursor_pagination_navigation()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);

        // Create 5 messages
        Chat::message('message 1')->from($userModel)->to($conversation)->send();
        Chat::message('message 2')->from($clientModel)->to($conversation)->send();
        Chat::message('message 3')->from($userModel)->to($conversation)->send();
        Chat::message('message 4')->from($clientModel)->to($conversation)->send();
        Chat::message('message 5')->from($userModel)->to($conversation)->send();

        // Get first page with 2 items
        $parameters = [
            $conversation->getKey(),
            'participant_id'   => $userModel->getKey(),
            'participant_type' => $userModel->getMorphClass(),
            'perPage'          => 2,
            'sorting'          => 'asc',
        ];

        $firstPage = $this->getJson(route('conversations.messages.index.cursor', $parameters))
            ->assertStatus(200);

        $this->assertCount(2, $firstPage->json('data'));
        $this->assertEquals('message 1', $firstPage->json('data.0.body'));
        $this->assertEquals('message 2', $firstPage->json('data.1.body'));
        $this->assertNotNull($firstPage->json('next_cursor'));

        // Get second page using cursor
        $nextCursor           = $firstPage->json('next_cursor');
        $parameters['cursor'] = $nextCursor;

        $secondPage = $this->getJson(route('conversations.messages.index.cursor', $parameters))
            ->assertStatus(200);

        $this->assertCount(2, $secondPage->json('data'));
        $this->assertEquals('message 3', $secondPage->json('data.0.body'));
        $this->assertEquals('message 4', $secondPage->json('data.1.body'));

        // Get third page
        $nextCursor           = $secondPage->json('next_cursor');
        $parameters['cursor'] = $nextCursor;

        $thirdPage = $this->getJson(route('conversations.messages.index.cursor', $parameters))
            ->assertStatus(200);

        $this->assertCount(1, $thirdPage->json('data'));
        $this->assertEquals('message 5', $thirdPage->json('data.0.body'));
        $this->assertNull($thirdPage->json('next_cursor'));
    }

    public function test_index_with_cursor_descending_order()
    {
        $conversation = factory(Conversation::class)->create();
        $userModel    = factory(User::class)->create();
        $clientModel  = factory(Client::class)->create();

        Chat::conversation($conversation)->addParticipants([$userModel, $clientModel]);
        Chat::message('message 1')->from($userModel)->to($conversation)->send();
        Chat::message('message 2')->from($clientModel)->to($conversation)->send();
        Chat::message('message 3')->from($userModel)->to($conversation)->send();

        $parameters = [
            $conversation->getKey(),
            'participant_id'   => $userModel->getKey(),
            'participant_type' => $userModel->getMorphClass(),
            'perPage'          => 2,
            'sorting'          => 'desc',
        ];

        $response = $this->getJson(route('conversations.messages.index.cursor', $parameters))
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
        // In descending order, newest messages come first
        $this->assertEquals('message 3', $response->json('data.0.body'));
        $this->assertEquals('message 2', $response->json('data.1.body'));
    }

    public function test_index_with_cursor_requires_participant()
    {
        $conversation = factory(Conversation::class)->create();

        $parameters = [
            $conversation->getKey(),
            'perPage' => 10,
        ];

        $this->getJson(route('conversations.messages.index.cursor', $parameters))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['participant_id', 'participant_type']);
    }
}
