<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ChatTest extends TestCase
{
    use DatabaseMigrations;

    protected $conversation;

    protected $prefix = 'mc_';

    /** @test */
    public function it_creates_a_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $this->assertDatabaseHas($this->prefix.'conversations', ['id' => 1]);
    }

    /** @test */
    public function it_returns_a_conversation_given_the_id()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $c = Chat::conversation($conversation->id);

        $this->assertEquals($conversation->id, $c->id);
    }

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
    public function it_can_mark_a_conversation_as_read()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();

        Chat::conversations($conversation)->for($this->users[0])->readAll();

        $user = $this->users[0];

        $notifications = $this->users[0]->unreadNotifications->filter(function ($item) use ($user, $conversation) {
            return $item->type == 'Musonza\Chat\Notifications\MessageSent' &&
            $item->data['conversation_id'] == $conversation->id &&
            $item->notifiable_id == $user->id;
        });

        $this->assertEquals(0, $notifications->count());
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

    /** @test  */
    public function it_can_update_conversation_details()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $data = ['title' => 'PHP Channel', 'description' => 'PHP Channel Description'];

        $conversation->update(['data' => $data]);

        $this->assertEquals('PHP Channel', $conversation->data['title']);
        $this->assertEquals('PHP Channel Description', $conversation->data['description']);
    }

    /** @test  */
    public function it_can_clear_a_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        Chat::message('Hello there 0')->from($this->users[0])->to($conversation)->send();
        Chat::message('Hello there 1')->from($this->users[0])->to($conversation)->send();
        Chat::message('Hello there 2')->from($this->users[0])->to($conversation)->send();

        $perPage = 5;
        $page = 1;

        Chat::conversations($conversation)->for($this->users[0])->clear();

        $messages = Chat::conversations($conversation)->for($this->users[0])->getMessages($perPage, $page);

        $this->assertEquals($messages->count(), 0);
    }

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
    public function it_can_tell_message_sender()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        Chat::message('Hello')->from($this->users[0])->to($conversation)->send();

        $this->assertEquals($conversation->messages[0]->sender->email, $this->users[0]->email);
    }

    /** @test */
    public function it_can_create_a_conversation_between_two_users()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $this->assertCount(2, $conversation->users);
    }

    /** @test */
    public function it_can_return_a_conversation_between_users()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $conversation2 = Chat::createConversation([$this->users[0]->id, $this->users[2]->id]);

        $conversation3 = Chat::createConversation([$this->users[0]->id, $this->users[3]->id]);

        $c1 = Chat::getConversationBetween($this->users[0], $this->users[1]);

        $this->assertEquals($conversation->id, $c1->id);

        $c3 = Chat::getConversationBetween($this->users[0], $this->users[3]);

        $this->assertEquals($conversation3->id, $c3->id);
    }

    /** @test */
    public function it_can_remove_a_single_user_from_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $conversation = Chat::removeParticipants($conversation, $this->users[0]);

        $this->assertEquals(1, $conversation->fresh()->users()->count());
    }

    /** @test */
    public function it_can_remove_multiple_users_from_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $conversation = Chat::removeParticipants($conversation, [$this->users[0], $this->users[1]]);

        $this->assertEquals(0, $conversation->fresh()->users->count());
    }

    /** @test */
    public function it_can_add_a_single_user_to_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $this->assertEquals($conversation->users->count(), 2);

        $userThree = $this->createUsers(1);

        Chat::addParticipants($conversation, $userThree[0]);

        $this->assertEquals($conversation->fresh()->users->count(), 3);
    }

    /** @test */
    public function it_can_add_multiple_users_to_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $this->assertEquals($conversation->users->count(), 2);

        $otherUsers = $this->createUsers(5);

        Chat::addParticipants($conversation, $otherUsers);

        $this->assertEquals($conversation->fresh()->users->count(), 7);
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
    public function it_can_return_conversation_recent_messsage()
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

        sleep(1);

        $message7 = Chat::message('Hello Man 10')->from($this->users[0])->to($conversation2)->send();

        $this->assertEquals($message7->id, $conversation2->last_message->id);
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

        sleep(1);

        Chat::message('Hello Man 10')->from($this->users[0])->to($conversation2)->send();

        $recent_messages = Chat::conversations()->for($this->users[0])->limit(5)->page(1)->get();

        $this->assertCount(3, $recent_messages);
    }

    /** @test */
    public function it_can_return_a_common_conversation_among_users()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);
        Chat::message('Hello 1')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello 2')->from($this->users[0])->to($conversation)->send();

        $conversation2 = Chat::createConversation([$this->users[0]->id, $this->users[2]->id]);
        Chat::message('Hello Man 4')->from($this->users[0])->to($conversation2)->send();

        $conversation3 = Chat::createConversation([$this->users[0]->id, $this->users[1]->id, $this->users[3]->id]);
        Chat::message('Hello Man 5')->from($this->users[3])->to($conversation3)->send();
        Chat::message('Hello Man 6')->from($this->users[0])->to($conversation3)->send();
        Chat::message('Hello Man 3')->from($this->users[2])->to($conversation2)->send();

        $users = \Musonza\Chat\User::whereIn('id', [1, 2, 4])->get();

        $conversations = Chat::commonConversations($users);

        $this->assertCount(1, $conversations);

        $this->assertEquals(3, $conversations->first()->id);
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
    public function it_gets_unread_notifications()
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

        $this->assertEquals(3, Chat::for($this->users[0])->unreadCount());
        $this->assertEquals(2, $conversation1->unReadNotifications($this->users[0])->count());
        $this->assertEquals(1, $conversation2->unReadNotifications($this->users[0])->count());

        //Read message from from convo
        Chat::messages($conversation1->messages()->first())->for($this->users[0])->markRead();
        $this->assertEquals(2, Chat::for($this->users[0])->unreadCount());
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
