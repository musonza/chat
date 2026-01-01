<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Musonza\Chat\Models\Conversation;

class NotificationsTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_creates_message_notification()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::message('Hello there 0')->from($this->bravo)->to($conversation)->send();
        Chat::message('Hello there 1')->from($this->alpha)->to($conversation)->send();
        Chat::message('Hello there 2')->from($this->alpha)->to($conversation)->send();

        Chat::message('Hello there 3')->from($this->bravo)->to($conversation)->send();
        Chat::message('Hello there 4')->from($this->bravo)->to($conversation)->send();
        Chat::message('Hello there 5')->from($this->bravo)->to($conversation)->send();

        $this->assertEquals(6, $conversation->getNotifications($this->bravo)->count());
        $this->assertEquals(6, $conversation->getNotifications($this->alpha)->count());
        $this->assertEquals(0, $conversation->getNotifications($this->charlie)->count());
    }

    /** @test */
    public function it_gets_all_unread_notifications()
    {
        $conversation1 = Chat::createConversation([$this->alpha, $this->bravo]);
        Chat::message('Hello 1')->from($this->bravo)->to($conversation1)->send();
        Chat::message('Hello 2')->from($this->bravo)->to($conversation1)->send();
        $conversation2 = Chat::createConversation([$this->charlie, $this->alpha]);
        Chat::message('Hello 3')->from($this->charlie)->to($conversation2)->send();

        $notifications = Chat::setParticipant($this->alpha)->unReadNotifications();

        $this->assertEquals(3, $notifications->count());
    }

    /** @test */
    public function it_gets_unread_notifications_per_conversation()
    {
        /** @var Conversation $conversation1 */
        $conversation1 = Chat::createConversation([$this->alpha, $this->bravo]);
        Chat::message('Hello 1')->from($this->bravo)->to($conversation1)->send();
        Chat::message('Hello 2')->from($this->bravo)->to($conversation1)->send();
        $conversation2 = Chat::createConversation([$this->charlie, $this->alpha]);
        Chat::message('Hello 3')->from($this->charlie)->to($conversation2)->send();

        $this->assertEquals(3, Chat::messages()->setParticipant($this->alpha)->unreadCount());
        $this->assertEquals(2, Chat::conversation($conversation1)->setParticipant($this->alpha)->unreadCount());
        $this->assertEquals(1, Chat::conversation($conversation2)->setParticipant($this->alpha)->unreadCount());

        // Read message from from convo
        Chat::message($conversation1->messages()->first())->setParticipant($this->alpha)->markRead();
        $this->assertEquals(2, Chat::messages()->setParticipant($this->alpha)->unreadCount());
    }
}
