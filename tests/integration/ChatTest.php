<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChatTest extends TestCase
{
    use DatabaseTransactions;

    protected $conversation;

    public function __construct()
    {
        parent::__construct();
    }

    public function setUp()
    {
        parent::setUp();
    }

    /** @test */
    public function it_creates_a_conversation()
    {
        Chat::createConversation();
        $this->seeInDatabase('conversations', ['id' => 1]);
    }

    /** @test */
    public function it_returns_a_conversation_given_the_id()
    {
        $conversation = Chat::createConversation();

        $c = Chat::conversation($conversation->id);

        $this->assertEquals($conversation->id, $c->id);
    }

    /** @test */
    public function it_can_send_a_message()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        Chat::send($conversation->id, 'Hello', $users[0]->id);

        $this->assertEquals($conversation->messages->count(), 1);
    }

    /** @test */
    public function it_can_mark_a_message_as_read()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        Chat::send($conversation->id, 'Hello there 0', $users[1]->id);

        $messageId = 1;

        Chat::messageRead($messageId, $users[0]->id);

        $this->seeInDatabase('message_notification', ['message_id' => 1, 'user_id' => $users[0]->id, 'is_seen' => 1]);
    }

    /** @test */
    public function it_can_mark_a_conversation_as_read()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        Chat::send($conversation->id, 'Hello there 0', $users[1]->id);
        Chat::send($conversation->id, 'Hello there 0', $users[1]->id);
        Chat::send($conversation->id, 'Hello there 0', $users[1]->id);

        Chat::conversationRead($conversation->id, $users[0]->id);

        $this->seeInDatabase('message_notification', ['message_id' => 1, 'user_id' => $users[0]->id, 'is_seen' => 1]);
        $this->seeInDatabase('message_notification', ['message_id' => 2, 'user_id' => $users[0]->id, 'is_seen' => 1]);
        $this->seeInDatabase('message_notification', ['message_id' => 3, 'user_id' => $users[0]->id, 'is_seen' => 1]);
    }

    /** @test */
    public function it_can_delete_a_message()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        Chat::send($conversation->id, 'Hello there 0', $users[0]->id);

        $messageId = 1;
        $perPage = 5;
        $page = 1;

        Chat::trash($messageId, $users[0]->id);

        $this->dontSeeInDatabase('message_notification', ['message_id' => $messageId, 'user_id' => $users[0]->id, 'deleted_at' => null]);

        $this->assertEquals(Chat::messages($users[0]->id, $conversation->id, $perPage, $page)->count(), 0);
    }

    /** @test  */
    public function it_can_clear_a_conversation()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        Chat::send($conversation->id, 'Hello there 0', $users[0]->id);
        Chat::send($conversation->id, 'Hello there 1', $users[0]->id);
        Chat::send($conversation->id, 'Hello there 2', $users[0]->id);

        $perPage = 5;
        $page = 1;

        Chat::clear($conversation->id, $users[0]->id);

        $this->assertEquals(Chat::messages($users[0]->id, $conversation->id, $perPage, $page)->count(), 0);
    }

    /** @test */
    public function it_creates_message_notification()
    {
        $users = $this->createUsers(4);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        Chat::send($conversation->id, 'Hello there 0', $users[1]->id);
        Chat::send($conversation->id, 'Hello there 1', $users[0]->id);
        Chat::send($conversation->id, 'Hello there 2', $users[0]->id);

        Chat::send($conversation->id, 'Hello there 3', $users[1]->id);
        Chat::send($conversation->id, 'Hello there 4', $users[1]->id);
        Chat::send($conversation->id, 'Hello there 5', $users[1]->id);

        $this->seeInDatabase('message_notification', ['id' => 1]);
    }

    /** @test */
    public function it_can_tell_message_sender()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        Chat::send($conversation->id, 'Hello', $users[0]->id);

        $this->assertEquals($conversation->messages[0]->sender->email, $users[0]->email);
    }

    /** @test */
    public function it_can_create_a_conversation_between_two_users()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        $this->assertCount(2, $conversation->users);
    }

    /** @test */
    public function it_can_return_a_conversation_between_users()
    {
        $users = $this->createUsers(5);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        $conversation2 = Chat::createConversation([$users[0]->id, $users[2]->id]);

        $conversation3 = Chat::createConversation([$users[0]->id, $users[3]->id]);

        $c1 = Chat::getConversationBetweenUsers($users[0]->id, $users[1]->id);

        $this->assertEquals($conversation->id, $c1->id);

        $c3 = Chat::getConversationBetweenUsers($users[0]->id, $users[3]->id);

        $this->assertEquals($conversation3->id, $c3->id);
    }

    /** @test */
    public function it_can_remove_a_single_user_from_conversation()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        $conversation = Chat::removeParticipants($conversation->id, $users[0]->id);

        $this->assertEquals($conversation->users->count(), 1);
    }

    /** @test */
    public function it_can_remove_multiple_users_from_conversation()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        $conversation = Chat::removeParticipants($conversation->id, $users->toArray());

        $this->assertEquals($conversation->users->count(), 0);
    }

    /** @test */
    public function it_can_add_a_single_user_to_conversation()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        $this->assertEquals($conversation->users->count(), 2);

        $userThree = $this->createUsers(1);

        Chat::addParticipants($conversation->id, $userThree->id);

        $this->assertEquals($conversation->fresh()->users->count(), 3);
    }

    /** @test */
    public function it_can_add_multiple_users_to_conversation()
    {
        $users = $this->createUsers(2);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        $this->assertEquals($conversation->users->count(), 2);

        $otherUsers = $this->createUsers(5);

        Chat::addParticipants($conversation->id, $otherUsers);

        $this->assertEquals($conversation->fresh()->users->count(), 7);
    }

    /** @test */
    public function it_can_return_paginated_messages_in_a_conversation()
    {
        $users = $this->createUsers(3);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);

        for ($i = 0; $i < 50; $i++) {
            Chat::send($conversation->id, 'Hello '.$i, $users[0]->id);
            Chat::send($conversation->id, 'Hello Man '.$i, $users[1]->id);
        }

        Chat::send($conversation->id, 'Hello Man', $users[1]->id);

        $this->assertEquals($conversation->messages->count(), 101);

        $perPage = 50;

        $page = 1;

        $this->assertEquals(Chat::messages($users[0]->id, $conversation->id, $perPage, $page)->count(), 50);

        $this->assertEquals(Chat::messages($users[0]->id, $conversation->id, $perPage, $page + 1)->count(), 50);

        $this->assertEquals(Chat::messages($users[0]->id, $conversation->id, $perPage, $page + 2)->count(), 1);

        $this->assertEquals(Chat::messages($users[0]->id, $conversation->id, $perPage, $page + 3)->count(), 0);
    }

    /** @test */
    public function it_can_return_recent_user_messsages()
    {
        $users = $this->createUsers(4);

        $conversation = Chat::createConversation([$users[0]->id, $users[1]->id]);
        Chat::send($conversation->id, 'Hello 1', $users[1]->id);
        Chat::send($conversation->id, 'Hello 2', $users[0]->id);

        $conversation2 = Chat::createConversation([$users[0]->id, $users[2]->id]);
        Chat::send($conversation2->id, 'Hello Man 4', $users[0]->id);
        Chat::send($conversation2->id, 'Hello Man 3', $users[2]->id);

        $conversation3 = Chat::createConversation([$users[0]->id, $users[3]->id]);
        Chat::send($conversation3->id, 'Hello Man 5', $users[3]->id);
        Chat::send($conversation3->id, 'Hello Man 6', $users[0]->id);

        $recent_messages = Chat::conversations($users[0]->id);

        $this->assertEquals($recent_messages[0]['sender']['id'], $users[0]->id);
        $this->assertEquals($recent_messages[1]['sender']['id'], $users[2]->id);

        $this->assertCount(3, $recent_messages);
    }

    public function createUsers($count = 1)
    {
        return factory('App\User', $count)->create();
    }
}
