<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class MessageTest extends TestCase
{
    use DatabaseMigrations;

    protected $conversation;
    protected $prefix = 'mc_';

    /** @test */
    public function it_can_send_a_message()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        Chat::message('Hello')
            ->from($this->users[0])
            ->to($conversation)
            ->send();

        $this->assertEquals($conversation->messages->count(), 1);
    }

    /** @test */
    public function it_returns_a_message_given_the_id()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $message = Chat::message('Hello')
            ->from($this->users[0])
            ->to($conversation)
            ->send();

        $m = Chat::messageWithId($message->id);

        $this->assertEquals($message->id, $m->id);
    }

    /** @test */
    public function it_can_send_a_message_and_specificy_type()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $message = Chat::message('http://example.com/my-cool-image')
            ->type('image')
            ->from($this->users[0])
            ->to($conversation)
            ->send();

        $this->assertEquals('image', $message->type);
    }

    /** @test */
    public function it_can_mark_a_message_as_read()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $message = Chat::message('Hello there 0')
            ->from($this->users[1])
            ->to($conversation)
            ->send();

        Chat::messages($message)->for($this->users[0])->markRead();

        $this->assertNotNull($message->getNotification($this->users[0])->read_at);
    }

    /** @test */
    public function it_can_delete_a_message()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);
        $message = Chat::message('Hello there 0')->from($this->users[0])->to($conversation)->send();

        $messageId = 1;
        $perPage = 5;
        $page = 1;

        Chat::messages($message)->for($this->users[0])->delete();

        $messages = Chat::conversations($conversation)->for($this->users[0])->getMessages($perPage, $page);

        $this->assertEquals($messages->count(), 0);
    }

    /** @test */
    public function it_can_tell_message_sender()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        Chat::message('Hello')->from($this->users[0])->to($conversation)->send();

        $this->assertEquals($conversation->messages[0]->sender->email, $this->users[0]->email);
    }

    /** @test */
    public function it_can_return_paginated_messages_in_a_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        for ($i = 0; $i < 3; $i++) {
            Chat::message('Hello '.$i)->from($this->users[0])->to($conversation)->send();
            Chat::message('Hello Man '.$i)->from($this->users[1])->to($conversation)->send();
        }

        Chat::message('Hello Man')->from($this->users[1])->to($conversation)->send();

        $this->assertEquals($conversation->messages->count(), 7);

        $perPage = 3;

        $page = 1;

        $this->assertEquals(3, Chat::conversations($conversation)->for($this->users[0])->getMessages($perPage, $page)->count());
        $this->assertEquals(3, Chat::conversations($conversation)->for($this->users[0])->getMessages($perPage, 2)->count());
        $this->assertEquals(1, Chat::conversations($conversation)->for($this->users[0])->getMessages($perPage, 3)->count());
        $this->assertEquals(0, Chat::conversations($conversation)->for($this->users[0])->getMessages($perPage, 4)->count());
    }

    /** @test */
    public function it_can_return_recent_user_messsages()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);
        Chat::message('Hello 1')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello 2')->from($this->users[0])->to($conversation)->send();

        $conversation2 = Chat::createConversation([$this->users[0]->id, $this->users[2]->id]);
        Chat::message('Hello Man 4')->from($this->users[0])->to($conversation2)->send();

        $conversation3 = Chat::createConversation([$this->users[0]->id, $this->users[3]->id]);
        Chat::message('Hello Man 5')->from($this->users[3])->to($conversation3)->send();
        Chat::message('Hello Man 6')->from($this->users[0])->to($conversation3)->send();
        Chat::message('Hello Man 3')->from($this->users[2])->to($conversation2)->send();
        Chat::message('Hello Man 10')->from($this->users[0])->to($conversation2)->send();

        $recent_messages = Chat::conversations()->for($this->users[0])->limit(5)->page(1)->get();

        $this->assertCount(3, $recent_messages);
    }

    /** @test */
    public function it_return_unread_messages_count_for_user()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);
        Chat::message('Hello 1')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello 2')->from($this->users[0])->to($conversation)->send();
        $message = Chat::message('Hello 2')->from($this->users[0])->to($conversation)->send();

        $this->assertEquals(2, Chat::for($this->users[1])->unreadCount());
        $this->assertEquals(1, Chat::for($this->users[0])->unreadCount());

        Chat::messages($message)->for($this->users[1])->markRead();

        $this->assertEquals(1, Chat::for($this->users[1])->unreadCount());
    }

    /** @test */
    public function it_gets_a_message_by_id()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);
        Chat::message('Hello 1')->from($this->users[1])->to($conversation)->send();
        $message = Chat::messageById(1);

        $this->assertInstanceOf('Musonza\Chat\Messages\Message', $message);
        $this->assertEquals(1, $message->id);
    }
}