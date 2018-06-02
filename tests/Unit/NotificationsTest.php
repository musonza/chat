<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class NotificationsTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_creates_message_notification()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello there 1')->from($this->users[0])->to($conversation)->send();
        Chat::message('Hello there 2')->from($this->users[0])->to($conversation)->send();

        Chat::message('Hello there 3')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello there 4')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello there 5')->from($this->users[1])->to($conversation)->send();

        $this->assertEquals(6, $conversation->getNotifications($this->users[1])->count());
        $this->assertEquals(6, $conversation->getNotifications($this->users[0])->count());
        $this->assertEquals(0, $conversation->getNotifications($this->users[2])->count());
    }

    /** @test */
    public function it_gets_all_unread_notifications()
    {
        $conversation1 = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);
        Chat::message('Hello 1')->from($this->users[1])->to($conversation1)->send();
        Chat::message('Hello 2')->from($this->users[1])->to($conversation1)->send();
        $conversation2 = Chat::createConversation([$this->users[2]->id, $this->users[0]->id]);
        Chat::message('Hello 3')->from($this->users[2])->to($conversation2)->send();

        $notifications = Chat::for($this->users[0])->unReadNotifications();

        $this->assertEquals(3, $notifications->count());
    }

    /** @test */
    public function it_gets_unread_notifications_per_conversation()
    {
        $conversation1 = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);
        Chat::message('Hello 1')->from($this->users[1])->to($conversation1)->send();
        Chat::message('Hello 2')->from($this->users[1])->to($conversation1)->send();
        $conversation2 = Chat::createConversation([$this->users[2]->id, $this->users[0]->id]);
        Chat::message('Hello 3')->from($this->users[2])->to($conversation2)->send();

        $this->assertEquals(3, Chat::messages()->for($this->users[0])->unreadCount());
        $this->assertEquals(2, Chat::conversation($conversation1)->for($this->users[0])->unreadCount());
        $this->assertEquals(1, Chat::conversation($conversation2)->for($this->users[0])->unreadCount());

        //Read message from from convo
        Chat::message($conversation1->messages()->first())->for($this->users[0])->markRead();
        $this->assertEquals(2, Chat::messages()->for($this->users[0])->unreadCount());
    }
}
