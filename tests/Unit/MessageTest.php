<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Musonza\Chat\ConfigurationManager;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message;
use Musonza\Chat\Tests\Helpers\Models\Bot;
use Musonza\Chat\Tests\Helpers\Models\Client;
use Musonza\Chat\Tests\Helpers\Models\User;

class MessageTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_send_a_message()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::message('Hello')
            ->from($this->bravo)
            ->to($conversation)
            ->send();

        $this->assertEquals($conversation->messages->count(), 1);
    }

    /** @test */
    public function it_can_send_a_message_between_models()
    {
        /** @var Client $clientModel */
        $clientModel = factory(Client::class)->create();
        $userModel   = factory(User::class)->create();
        $botModel    = factory(Bot::class)->create();

        $conversation = Chat::createConversation([$clientModel, $userModel, $botModel]);

        Chat::message('Hello')
            ->from($userModel)
            ->to($conversation)
            ->send();

        $this->assertEquals($conversation->messages->count(), 1);
    }

    /** @test */
    public function it_returns_a_message_given_the_id()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        $message = Chat::message('Hello')
            ->from($this->alpha)
            ->to($conversation)
            ->send();

        $m = Chat::messages()->getById($message->id);

        $this->assertEquals($message->id, $m->id);
    }

    /** @test */
    public function it_can_send_a_message_and_specificy_type()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        $message = Chat::message('http://example.com/my-cool-image')
            ->type('image')
            ->from($this->alpha)
            ->to($conversation)
            ->send();

        $this->assertEquals('image', $message->type);
    }

    /** @test */
    public function it_can_mark_a_message_as_read()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        $message = Chat::message('Hello there 0')
            ->from($this->bravo)
            ->to($conversation)
            ->send();

        Chat::message($message)->setParticipant($this->alpha)->markRead();

        $this->assertNotNull($message->getNotification($this->alpha)->read_at);
    }

    /** @test */
    public function it_can_delete_a_message()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello there 0')->from($this->alpha)->to($conversation)->send();
        $perPage      = 5;
        $page         = 1;

        Chat::message($message)->setParticipant($this->bravo)->delete();

        $messages = Chat::conversation($conversation)->setParticipant($this->bravo)->getMessages($perPage, $page);

        $this->assertEquals(0, $messages->count());
    }

    /** @test */
    public function it_can_list_deleted_messages()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello there 0')->from($this->alpha)->to($conversation)->send();

        $perPage = 5;
        $page    = 1;

        Chat::message($message)->setParticipant($this->bravo)->delete();

        $messages = Chat::conversation($conversation)
            ->setParticipant($this->bravo)
            ->deleted()
            ->getMessages($perPage, $page);

        $this->assertEquals(1, $messages->count());
    }

    /** @test */
    public function it_can_tell_message_sender_participation()
    {
        /** @var Conversation $conversation */
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        $this->assertEquals(
            $conversation->messages[0]->participation->getKey(),
            $conversation->messages[0]->participation_id
        );
    }

    /** @test */
    public function it_can_tell_message_sender()
    {
        $bot    = factory(Bot::class)->create();
        $client = factory(Client::class)->create();

        $conversation = Chat::createConversation([$this->alpha, $client, $bot]);
        Chat::message('Hello')->from($this->alpha)->to($conversation)->send();
        Chat::message('Hello')->from($bot)->to($conversation)->send();

        $this->assertInstanceOf(User::class, $conversation->messages[0]->sender);
        $this->assertInstanceOf(Bot::class, $conversation->messages[1]->sender);
    }

    /** @test */
    public function it_can_return_paginated_messages_in_a_conversation()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        for ($i = 0; $i < 3; $i++) {
            Chat::message('Hello ' . $i)->from($this->alpha)->to($conversation)->send();
            Chat::message('Hello Man ' . $i)->from($this->bravo)->to($conversation)->send();
        }

        Chat::message('Hello Man')->from($this->bravo)->to($conversation)->send();

        $this->assertEquals($conversation->messages->count(), 7);
        $this->assertEquals(3, Chat::conversation($conversation)->setParticipant($this->alpha)->perPage(3)->getMessages()->count());
        $this->assertEquals(3, Chat::conversation($conversation)->setParticipant($this->alpha)->perPage(3)->page(2)->getMessages()->count());
        $this->assertEquals(1, Chat::conversation($conversation)->setParticipant($this->alpha)->perPage(3)->page(3)->getMessages()->count());
        $this->assertEquals(0, Chat::conversation($conversation)->setParticipant($this->alpha)->perPage(3)->page(4)->getMessages()->count());
    }

    /** @test */
    public function it_can_return_recent_user_messsages()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        Chat::message('Hello 1')->from($this->bravo)->to($conversation)->send();
        Chat::message('Hello 2')->from($this->alpha)->to($conversation)->send();

        $conversation2 = Chat::createConversation([$this->alpha, $this->charlie]);
        Chat::message('Hello Man 4')->from($this->alpha)->to($conversation2)->send();

        $conversation3 = Chat::createConversation([$this->alpha, $this->delta]);
        Chat::message('Hello Man 5')->from($this->delta)->to($conversation3)->send();
        Chat::message('Hello Man 6')->from($this->alpha)->to($conversation3)->send();
        Chat::message('Hello Man 3')->from($this->charlie)->to($conversation2)->send();
        Chat::message('Hello Man 10')->from($this->alpha)->to($conversation2)->send();

        $recent_messages = Chat::conversations()->setParticipant($this->alpha)->limit(5)->page(1)->get();
        $this->assertCount(3, $recent_messages);

        $recent_messages = Chat::conversations()->setParticipant($this->alpha)->setPaginationParams([
            'perPage'  => 1,
            'page'     => 1,
            'pageName' => 'test',
            'sorting'  => 'desc',
        ])->get();

        $this->assertCount(1, $recent_messages);
    }

    /** @test */
    public function it_return_unread_messages_count_for_user()
    {
        [$this->alpha, $this->bravo] = $this->users;

        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        Chat::message('Hello 1')->from($this->bravo)->to($conversation)->send();
        Chat::message('Hello 2')->from($this->alpha)->to($conversation)->send();
        $message = Chat::message('Hello 2')->from($this->alpha)->to($conversation)->send();

        $this->assertEquals(2, Chat::messages()->setParticipant($this->bravo)->unreadCount());
        $this->assertEquals(1, Chat::messages()->setParticipant($this->alpha)->unreadCount());

        Chat::message($message)->setParticipant($this->bravo)->markRead();

        $this->assertEquals(1, Chat::messages()->setParticipant($this->bravo)->unreadCount());
    }

    /** @test */
    public function it_gets_a_message_by_id()
    {
        [$this->alpha, $this->bravo] = $this->users;

        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        Chat::message('Hello 1')->from($this->bravo)->to($conversation)->send();
        $message = Chat::messages()->getById(1);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(1, $message->id);
    }

    /** @test */
    public function it_flags_a_message()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')
            ->from($this->alpha)
            ->to($conversation)
            ->send();

        Chat::message($message)->setParticipant($this->bravo)->toggleFlag();
        $this->assertTrue(Chat::message($message)->setParticipant($this->bravo)->flagged());

        Chat::message($message)->setParticipant($this->bravo)->toggleFlag();
        $this->assertFalse(Chat::message($message)->setParticipant($this->bravo)->flagged());
    }

    /** @test */
    public function it_specifies_fields_to_return_for_sender()
    {
        $this->app['config']->set('musonza_chat.sender_fields_whitelist', [
            'name', 'bot_id',
        ]);

        $bot    = factory(Bot::class)->create();
        $client = factory(Client::class)->create();

        $conversation = Chat::createConversation([$client, $bot]);
        Chat::message('Hello')->from($bot)->to($conversation)->send();

        $this->assertSame(['name', 'bot_id'], array_keys($conversation->messages[0]->sender));
    }

    /** @test */
    public function it_stores_message_unencrypted_when_encryption_disabled()
    {
        $this->app['config']->set('musonza_chat.encrypt_messages', false);

        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello World')->from($this->alpha)->to($conversation)->send();

        // Check the raw database value is not encrypted
        $rawMessage = DB::table(ConfigurationManager::MESSAGES_TABLE)->where('id', $message->id)->first();

        $this->assertEquals('Hello World', $rawMessage->body);
        $this->assertFalse((bool) $rawMessage->is_encrypted);
    }

    /** @test */
    public function it_encrypts_message_body_when_encryption_enabled()
    {
        $this->app['config']->set('musonza_chat.encrypt_messages', true);

        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Secret Message')->from($this->alpha)->to($conversation)->send();

        // Check the raw database value is encrypted (not plain text)
        $rawMessage = DB::table(ConfigurationManager::MESSAGES_TABLE)->where('id', $message->id)->first();

        $this->assertNotEquals('Secret Message', $rawMessage->body);
        $this->assertTrue((bool) $rawMessage->is_encrypted);
    }

    /** @test */
    public function it_decrypts_message_body_when_reading()
    {
        $this->app['config']->set('musonza_chat.encrypt_messages', true);

        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Secret Message')->from($this->alpha)->to($conversation)->send();

        // Reading the message via model should decrypt it
        $retrievedMessage = Message::find($message->id);

        $this->assertEquals('Secret Message', $retrievedMessage->body);
    }

    /** @test */
    public function it_reads_unencrypted_messages_when_encryption_enabled()
    {
        // First, create a message without encryption (simulating existing data)
        $this->app['config']->set('musonza_chat.encrypt_messages', false);

        $conversation   = Chat::createConversation([$this->alpha, $this->bravo]);
        $unencryptedMsg = Chat::message('Old Message')->from($this->alpha)->to($conversation)->send();

        // Now enable encryption
        $this->app['config']->set('musonza_chat.encrypt_messages', true);

        // Create a new encrypted message
        $encryptedMsg = Chat::message('New Secret')->from($this->bravo)->to($conversation)->send();

        // Both messages should be readable
        $oldMessage = Message::find($unencryptedMsg->id);
        $newMessage = Message::find($encryptedMsg->id);

        $this->assertEquals('Old Message', $oldMessage->body);
        $this->assertFalse($oldMessage->is_encrypted);

        $this->assertEquals('New Secret', $newMessage->body);
        $this->assertTrue($newMessage->is_encrypted);
    }
}
