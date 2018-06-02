<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ConversationTest extends TestCase
{
    use DatabaseMigrations;

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

        $c = Chat::conversations()->getById($conversation->id);

        $this->assertEquals($conversation->id, $c->id);
    }

    /** @test */
    public function it_can_mark_a_conversation_as_read()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();

        Chat::conversation($conversation)->for($this->users[0])->readAll();
        $this->assertEquals(0, $conversation->unReadNotifications($this->users[0])->count());
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

        Chat::conversation($conversation)->for($this->users[0])->clear();

        $messages = Chat::conversation($conversation)->for($this->users[0])->getMessages();

        $this->assertEquals($messages->count(), 0);
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

        $c1 = Chat::conversations()->between($this->users[0], $this->users[1]);

        $this->assertEquals($conversation->id, $c1->id);

        $c3 = Chat::conversations()->between($this->users[0], $this->users[3]);

        $this->assertEquals($conversation3->id, $c3->id);
    }

    /** @test */
    public function it_can_remove_a_single_user_from_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $conversation = Chat::conversation($conversation)->removeParticipants($this->users[0]);

        $this->assertEquals(1, $conversation->fresh()->users()->count());
    }

    /** @test */
    public function it_can_remove_multiple_users_from_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $conversation = Chat::conversation($conversation)->removeParticipants([$this->users[0], $this->users[1]]);

        $this->assertEquals(0, $conversation->fresh()->users->count());
    }

    /** @test */
    public function it_can_add_a_single_user_to_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $this->assertEquals($conversation->users->count(), 2);

        $userThree = $this->createUsers(1);

        Chat::conversation($conversation)->addParticipants($userThree[0]);

        $this->assertEquals($conversation->fresh()->users->count(), 3);
    }

    /** @test */
    public function it_can_add_multiple_users_to_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->id, $this->users[1]->id]);

        $this->assertEquals($conversation->users->count(), 2);

        $otherUsers = $this->createUsers(5);

        Chat::conversation($conversation)->addParticipants($otherUsers);

        $this->assertEquals($conversation->fresh()->users->count(), 7);
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

        $conversations = Chat::conversations()->common($users);

        $this->assertCount(1, $conversations);

        $this->assertEquals(3, $conversations->first()->id);
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

        $message7 = Chat::message('Hello Man 10')->from($this->users[0])->to($conversation2)->send();

        $this->assertEquals($message7->id, $conversation2->last_message->id);
    }
}
