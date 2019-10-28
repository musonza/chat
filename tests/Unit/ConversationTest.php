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
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);

        $this->assertDatabaseHas($this->prefix.'conversations', ['id' => 1]);
    }

    /** @test */
    public function it_returns_a_conversation_given_the_id()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);

        $c = Chat::conversations()->getById($conversation->id);

        $this->assertEquals($conversation->id, $c->id);
    }

    /** @test */
    public function it_can_mark_a_conversation_as_read()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);

        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello there 0')->from($this->users[1])->to($conversation)->send();

        Chat::conversation($conversation)->setUser($this->users[0])->readAll();
        $this->assertEquals(0, $conversation->unReadNotifications($this->users[0])->count());
    }

    /** @test  */
    public function it_can_update_conversation_details()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);
        $data = ['title' => 'PHP Channel', 'description' => 'PHP Channel Description'];
        $conversation->update(['data' => $data]);

        $this->assertEquals('PHP Channel', $conversation->data['title']);
        $this->assertEquals('PHP Channel Description', $conversation->data['description']);
    }

    /** @test  */
    public function it_can_clear_a_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);

        Chat::message('Hello there 0')->from($this->users[0])->to($conversation)->send();
        Chat::message('Hello there 1')->from($this->users[0])->to($conversation)->send();
        Chat::message('Hello there 2')->from($this->users[0])->to($conversation)->send();

        Chat::conversation($conversation)->setUser($this->users[0])->clear();

        $messages = Chat::conversation($conversation)->setUser($this->users[0])->getMessages();

        $this->assertEquals($messages->count(), 0);
    }

    /** @test */
    public function it_can_create_a_conversation_between_two_users()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);

        $this->assertCount(2, $conversation->users);
    }

    /** @test */
    public function it_can_return_a_conversation_between_users()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);
        $conversation2 = Chat::createConversation([$this->users[0]->getKey(), $this->users[2]->getKey()]);
        $conversation3 = Chat::createConversation([$this->users[0]->getKey(), $this->users[3]->getKey()]);

        $c1 = Chat::conversations()->between($this->users[0], $this->users[1]);

        $this->assertEquals($conversation->id, $c1->id);

        $c3 = Chat::conversations()->between($this->users[0], $this->users[3]);

        $this->assertEquals($conversation3->id, $c3->id);
    }

    /** @test */
    public function it_can_remove_a_single_user_from_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);

        $conversation = Chat::conversation($conversation)->removeParticipants($this->users[0]);

        $this->assertEquals(1, $conversation->fresh()->users()->count());
    }

    /** @test */
    public function it_can_remove_multiple_users_from_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);

        $conversation = Chat::conversation($conversation)->removeParticipants([$this->users[0], $this->users[1]]);

        $this->assertEquals(0, $conversation->fresh()->users->count());
    }

    /** @test */
    public function it_can_add_a_single_user_to_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);

        $this->assertEquals($conversation->users->count(), 2);

        $userThree = $this->createUsers(1);

        Chat::conversation($conversation)->addParticipants($userThree[0]);

        $this->assertEquals($conversation->fresh()->users->count(), 3);
    }

    /** @test */
    public function it_can_add_multiple_users_to_conversation()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);

        $this->assertEquals($conversation->users->count(), 2);

        $otherUsers = $this->createUsers(5);

        Chat::conversation($conversation)->addParticipants($otherUsers);

        $this->assertEquals($conversation->fresh()->users->count(), 7);
    }

    /** @test */
    public function it_can_return_a_common_conversation_among_users()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);
        Chat::message('Hello 1')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello 2')->from($this->users[0])->to($conversation)->send();

        $conversation2 = Chat::createConversation([$this->users[0]->getKey(), $this->users[2]->getKey()]);
        Chat::message('Hello Man 4')->from($this->users[0])->to($conversation2)->send();

        $conversation3 = Chat::createConversation([
            $this->users[0]->getKey(),
            $this->users[1]->getKey(),
            $this->users[3]->getKey(),
        ]);
        Chat::message('Hello Man 5')->from($this->users[3])->to($conversation3)->send();
        Chat::message('Hello Man 6')->from($this->users[0])->to($conversation3)->send();
        Chat::message('Hello Man 3')->from($this->users[2])->to($conversation2)->send();

        $users = \Musonza\Chat\User::whereIn($this->userModelPrimaryKey, [1, 2, 4])->get();

        $conversations = Chat::conversations()->common($users);

        $this->assertCount(1, $conversations);

        $this->assertEquals(3, $conversations->first()->id);
    }

    /** @test */
    public function it_can_return_conversation_recent_messsage()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);
        Chat::message('Hello 1')->from($this->users[1])->to($conversation)->send();
        Chat::message('Hello 2')->from($this->users[0])->to($conversation)->send();

        $conversation2 = Chat::createConversation([$this->users[0]->getKey(), $this->users[2]->getKey()]);
        Chat::message('Hello Man 4')->from($this->users[0])->to($conversation2)->send();

        $conversation3 = Chat::createConversation([$this->users[0]->getKey(), $this->users[3]->getKey()]);
        Chat::message('Hello Man 5')->from($this->users[3])->to($conversation3)->send();
        Chat::message('Hello Man 6')->from($this->users[0])->to($conversation3)->send();
        Chat::message('Hello Man 3')->from($this->users[2])->to($conversation2)->send();

        $message7 = Chat::message('Hello Man 10')->from($this->users[0])->to($conversation2)->send();

        $this->assertEquals($message7->id, $conversation2->last_message->id);
    }

    /** @test */
    public function it_returns_last_message_as_null_when_the_very_last_message_was_deleted()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);
        $message = Chat::message('Hello & Bye')->from($this->users[0])->to($conversation)->send();
        Chat::message($message)->setUser($this->users[0])->delete();

        $conversations = Chat::conversations()->setUser($this->users[0])->get();

        $this->assertNull($conversations->get(0)->last_message);
    }

    /** @test */
    public function it_returns_correct_attributes_in_last_message()
    {
        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);
        Chat::message('Hello')->from($this->users[0])->to($conversation)->send();

        $conversations = Chat::conversations()->setUser($this->users[0])->get();

        $this->assertTrue((bool) $conversations->get(0)->last_message->is_seen);

        $conversations = Chat::conversations()->setUser($this->users[1])->get();

        $this->assertFalse((bool) $conversations->get(0)->last_message->is_seen);
    }

    /** @test */
    public function it_returns_the_correct_order_of_conversations_when_updated_at_is_duplicated()
    {
        $auth = $this->users[0];

        $conversation = Chat::createConversation([$auth->getKey(), $this->users[1]->getKey()]);
        Chat::message('Hello-'.$conversation->id)->from($auth)->to($conversation)->send();

        $conversation = Chat::createConversation([$auth->getKey(), $this->users[2]->getKey()]);
        Chat::message('Hello-'.$conversation->id)->from($auth)->to($conversation)->send();

        $conversation = Chat::createConversation([$auth->getKey(), $this->users[3]->getKey()]);
        Chat::message('Hello-'.$conversation->id)->from($auth)->to($conversation)->send();

        $conversations = Chat::conversations()->setPaginationParams(['sorting' => 'desc'])->setUser($auth)->limit(1)->page(1)->get();
        $this->assertEquals('Hello-3', $conversations->items()[0]->last_message->body);

        $conversations = Chat::conversations()->setPaginationParams(['sorting' => 'desc'])->setUser($auth)->limit(1)->page(2)->get();
        $this->assertEquals('Hello-2', $conversations->items()[0]->last_message->body);

        $conversations = Chat::conversations()->setPaginationParams(['sorting' => 'desc'])->setUser($auth)->limit(1)->page(3)->get();
        $this->assertEquals('Hello-1', $conversations->items()[0]->last_message->body);
    }

    /** @test */
    public function it_allows_setting_private_or_public_conversation()
    {
        $conversation = Chat::createConversation([
            $this->users[0]->getKey(),
            $this->users[1]->getKey(),
        ])
            ->makePrivate();

        $this->assertTrue($conversation->private);

        $conversation->makePrivate(false);

        $this->assertFalse($conversation->private);
    }

    /** @test */
    public function it_converts_at_least_3_participants_to_public_by_default()
    {
        $conversation = Chat::createConversation([
            $this->users[0]->getKey(),
            $this->users[1]->getKey(),
        ])
            ->makePrivate();

        $this->assertTrue($conversation->private);

        $conversation = Chat::conversation($conversation)->addParticipants($this->createUsers(1));

        $this->assertFalse($conversation->private);
    }

    /** @test */
    public function converting_at_least_three_participants_to_public_is_configurable()
    {
        $this->app['config']->set('musonza_chat.make_three_or_more_users_public', false);

        $conversation = Chat::createConversation([
            $this->users[0]->getKey(),
            $this->users[1]->getKey(),
        ])
            ->makePrivate();

        $this->assertTrue($conversation->private);

        $conversation = Chat::conversation($conversation)->addParticipants($this->createUsers(1));

        $this->assertTrue($conversation->private);
    }

    /** @test */
    public function it_filters_conversations_by_type()
    {
        Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()])->makePrivate();
        Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()])->makePrivate(false);
        Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()])->makePrivate();

        $allConversations = Chat::conversations()->setUser($this->users[0])->get();
        $this->assertCount(3, $allConversations);

        $privateConversations = Chat::conversations()->setUser($this->users[0])->isPrivate()->get();
        $this->assertCount(2, $privateConversations);

        $publicConversations = Chat::conversations()->setUser($this->users[0])->isPrivate(false)->get();
        $this->assertCount(1, $publicConversations);
    }

    /** @test */
    public function it_specifies_fields_to_return_for_sender()
    {
        $this->app['config']->set('musonza_chat.sender_fields_whitelist', ['uid', 'email']);

        $conversation = Chat::createConversation([$this->users[0]->getKey(), $this->users[1]->getKey()]);
        $message = Chat::message('Hello')->from($this->users[0])->to($conversation)->send();

        $this->assertSame(['uid', 'email'], array_keys($message->sender->attributesToArray()));
    }
}
