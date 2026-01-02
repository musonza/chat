<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Musonza\Chat\Exceptions\DirectMessagingExistsException;
use Musonza\Chat\Exceptions\InvalidConversationListException;
use Musonza\Chat\Exceptions\InvalidDirectMessageNumberOfParticipants;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Participation;
use Musonza\Chat\Tests\Helpers\Models\Client;

class ConversationTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_creates_a_conversation()
    {
        Chat::createConversation([$this->alpha, $this->bravo]);

        $this->assertDatabaseHas($this->prefix . 'conversations', ['id' => 1]);
    }

    /** @test */
    public function it_returns_a_conversation_given_the_id()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        $c = Chat::conversations()->getById($conversation->id);

        $this->assertEquals($conversation->id, $c->id);
    }

    /** @test */
    public function it_returns_participant_conversations()
    {
        Chat::createConversation([$this->alpha, $this->bravo]);
        Chat::createConversation([$this->alpha, $this->charlie]);

        $this->assertEquals(2, $this->alpha->conversations()->count());
    }

    /** @test */
    public function it_can_mark_a_conversation_as_read()
    {
        /** @var Conversation $conversation */
        $conversation = Chat::createConversation([
            $this->alpha,
            $this->bravo,
        ])->makeDirect();

        Chat::message('Hello there 0')->from($this->bravo)->to($conversation)->send();
        Chat::message('Hello there 0')->from($this->bravo)->to($conversation)->send();
        Chat::message('Hello there 0')->from($this->bravo)->to($conversation)->send();
        Chat::message('Hello there 1')->from($this->alpha)->to($conversation)->send();

        Chat::conversation($conversation)->setParticipant($this->alpha)->readAll();
        $this->assertEquals(0, $conversation->unReadNotifications($this->alpha)->count());
        $this->assertEquals(1, $conversation->unReadNotifications($this->bravo)->count());
    }

    /** @test  */
    public function it_can_update_conversation_details()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $data         = ['title' => 'PHP Channel', 'description' => 'PHP Channel Description'];
        $conversation->update(['data' => $data]);

        $this->assertEquals('PHP Channel', $conversation->data['title']);
        $this->assertEquals('PHP Channel Description', $conversation->data['description']);
    }

    /** @test  */
    public function it_can_clear_a_conversation()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::message('Hello there 0')->from($this->alpha)->to($conversation)->send();
        Chat::message('Hello there 1')->from($this->alpha)->to($conversation)->send();
        Chat::message('Hello there 2')->from($this->alpha)->to($conversation)->send();

        Chat::conversation($conversation)->setParticipant($this->alpha)->clear();

        $messages = Chat::conversation($conversation)->setParticipant($this->alpha)->getMessages();

        $this->assertEquals($messages->count(), 0);
    }

    /** @test */
    public function it_can_create_a_conversation_between_two_users()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        $this->assertCount(2, $conversation->participants);
    }

    /** @test */
    public function it_can_remove_a_single_participant_from_conversation()
    {
        $clientModel  = factory(Client::class)->create();
        $conversation = Chat::createConversation([$this->alpha, $this->bravo, $clientModel]);
        $conversation = Chat::conversation($conversation)->removeParticipants($this->alpha);

        $this->assertEquals(2, $conversation->fresh()->participants()->count());

        $conversation = Chat::conversation($conversation)->removeParticipants($clientModel);
        $this->assertEquals(1, $conversation->fresh()->participants()->count());
    }

    /** @test */
    public function it_can_remove_multiple_users_from_conversation()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        $conversation = Chat::conversation($conversation)->removeParticipants([$this->alpha, $this->bravo]);

        $this->assertEquals(0, $conversation->fresh()->participants->count());
    }

    /** @test */
    public function it_can_add_a_single_user_to_conversation()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        $this->assertEquals($conversation->participants->count(), 2);

        $userThree = $this->createUsers(1);

        Chat::conversation($conversation)->addParticipants([$userThree[0]]);

        $this->assertEquals($conversation->fresh()->participants->count(), 3);
    }

    /** @test */
    public function it_can_add_multiple_users_to_conversation()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        $this->assertEquals($conversation->participants->count(), 2);

        $otherUsers = $this->createUsers(5);

        Chat::conversation($conversation)->addParticipants($otherUsers->all());

        $this->assertEquals($conversation->fresh()->participants->count(), 7);
    }

    /** @test */
    public function it_can_return_conversation_recent_messsage()
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

        $message7 = Chat::message('Hello Man 10')->from($this->alpha)->to($conversation2)->send();

        $this->assertEquals($message7->id, $conversation2->last_message->id);
    }

    /** @test */
    public function it_returns_last_message_as_null_when_the_very_last_message_was_deleted()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello & Bye')->from($this->alpha)->to($conversation)->send();
        Chat::message($message)->setParticipant($this->alpha)->delete();

        $conversations = Chat::conversations()->setParticipant($this->alpha)->get();

        $this->assertNull($conversations->first()->last_message);
    }

    /** @test */
    public function it_returns_correct_attributes_in_last_message()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        /** @var Collection $conversations */
        $conversations = Chat::conversations()->setParticipant($this->alpha)->get();

        $this->assertTrue((bool) $conversations->first()->conversation->last_message->is_seen);

        $conversations = Chat::conversations()->setParticipant($this->bravo)->get();

        $this->assertFalse((bool) $conversations->first()->conversation->last_message->is_seen);
    }

    /** @test */
    public function it_returns_the_correct_order_of_conversations_when_updated_at_is_duplicated()
    {
        $auth = $this->alpha;

        $conversation = Chat::createConversation([$auth, $this->bravo]);

        Chat::message('Hello-' . $conversation->id)->from($auth)->to($conversation)->send();

        $conversation = Chat::createConversation([$auth, $this->charlie]);
        Chat::message('Hello-' . $conversation->id)->from($auth)->to($conversation)->send();

        $conversation = Chat::createConversation([$auth, $this->delta]);
        Chat::message('Hello-' . $conversation->id)->from($auth)->to($conversation)->send();

        /** @var Collection $conversations */
        $conversations = Chat::conversations()->setPaginationParams(['sorting' => 'desc'])->setParticipant($auth)->limit(1)->page(1)->get();
        $this->assertEquals('Hello-3', $conversations->first()->conversation->last_message->body);

        $conversations = Chat::conversations()->setPaginationParams(['sorting' => 'desc'])->setParticipant($auth)->limit(1)->page(2)->get();
        $this->assertEquals('Hello-2', $conversations->first()->conversation->last_message->body);

        $conversations = Chat::conversations()->setPaginationParams(['sorting' => 'desc'])->setParticipant($auth)->limit(1)->page(3)->get();
        $this->assertEquals('Hello-1', $conversations->first()->conversation->last_message->body);
    }

    /** @test */
    public function it_allows_setting_private_or_public_conversation()
    {
        /** @var Conversation $conversation */
        $conversation = Chat::createConversation([
            $this->alpha,
            $this->bravo,
        ])->makePrivate();

        $this->assertTrue($conversation->private);

        $conversation->makePrivate(false);

        $this->assertFalse($conversation->private);
    }

    /**
     * DIRECT MESSAGING.
     *
     * @test
     */
    public function it_creates_direct_messaging()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo])
            ->makeDirect();

        $this->assertTrue($conversation->direct_message);
    }

    /** @test */
    public function it_does_not_duplicate_direct_messaging()
    {
        Chat::createConversation([$this->alpha, $this->bravo])
            ->makeDirect();

        $this->expectException(DirectMessagingExistsException::class);

        Chat::createConversation([$this->alpha, $this->bravo])
            ->makeDirect();
    }

    /** @test */
    public function it_prevents_additional_participants_to_direct_conversation()
    {
        /** @var Conversation $conversation */
        $conversation = Chat::createConversation([$this->alpha, $this->bravo])
            ->makeDirect();

        $this->expectException(InvalidDirectMessageNumberOfParticipants::class);
        $conversation->addParticipants([$this->charlie]);
    }

    /** @test */
    public function it_can_return_a_conversation_between_users()
    {
        /** @var Conversation $conversation */
        //        $conversation = Chat::makeDirect()->createConversation([$this->alpha, $this->bravo]);
        $conversation = Chat::createConversation([$this->alpha, $this->bravo])->makeDirect();

        Chat::createConversation([$this->alpha, $this->charlie]);
        $conversation3 = Chat::createConversation([$this->alpha, $this->delta])->makeDirect();

        $c1 = Chat::conversations()->between($this->alpha, $this->bravo);
        $this->assertEquals($conversation->id, $c1->id);

        $c3 = Chat::conversations()->between($this->alpha, $this->delta);
        $this->assertEquals($conversation3->id, $c3->id);
    }

    /** @test */
    public function it_filters_conversations_by_type()
    {
        Chat::createConversation([$this->alpha, $this->bravo])->makePrivate();
        Chat::createConversation([$this->alpha, $this->bravo])->makePrivate(false);
        Chat::createConversation([$this->alpha, $this->bravo])->makePrivate();
        Chat::createConversation([$this->alpha, $this->charlie])->makeDirect();

        $allConversations = Chat::conversations()->setParticipant($this->alpha)->get();
        $this->assertCount(4, $allConversations, 'All Conversations');

        $privateConversations = Chat::conversations()->setParticipant($this->alpha)->isPrivate()->get();
        $this->assertCount(3, $privateConversations, 'Private Conversations');

        $publicConversations = Chat::conversations()->setParticipant($this->alpha)->isPrivate(false)->get();
        $this->assertCount(1, $publicConversations, 'Public Conversations');

        $directConversations = Chat::conversations()->setParticipant($this->alpha)->isDirect()->get();

        $this->assertCount(1, $directConversations, 'Direct Conversations');
    }

    /**
     * Conversation Settings.
     *
     * @test
     */
    public function it_can_update_participant_conversation_settings()
    {
        /** @var Conversation $conversation */
        $conversation = Chat::createConversation([$this->alpha]);

        $settings = ['mute_mentions' => true];

        Chat::conversation($conversation)
            ->getParticipation($this->alpha)
            ->update(['settings' => $settings]);

        $this->assertEquals(
            $settings,
            $this->alpha->participation->where('conversation_id', $conversation->id)->first()->settings
        );
    }

    /** @test */
    public function it_can_get_participation_info_for_a_model()
    {
        /** @var Conversation $conversation */
        $conversation = Chat::createConversation([$this->alpha]);

        $participation = Chat::conversation($conversation)->setParticipant($this->alpha)->getParticipation();

        $this->assertInstanceOf(Participation::class, $participation);
    }

    /** @test */
    public function it_specifies_fields_to_return_for_sender()
    {
        $this->app['config']->set('musonza_chat.sender_fields_whitelist', ['uid', 'email']);

        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        $this->assertSame(['uid', 'email'], array_keys($message->sender));
    }

    /** @test */
    public function it_returns_correct_participation_for_specific_conversation()
    {
        // Create two conversations with the same participant
        $conversation1 = Chat::createConversation([$this->alpha, $this->bravo]);
        $conversation2 = Chat::createConversation([$this->alpha, $this->charlie]);

        // Get participation for each conversation
        $participation1 = Chat::conversation($conversation1)->getParticipation($this->alpha);
        $participation2 = Chat::conversation($conversation2)->getParticipation($this->alpha);

        // Verify each participation belongs to the correct conversation
        $this->assertEquals($conversation1->id, $participation1->conversation_id);
        $this->assertEquals($conversation2->id, $participation2->conversation_id);

        // Verify they are different participations
        $this->assertNotEquals($participation1->id, $participation2->id);
    }

    /** @test */
    public function it_returns_participant_details_from_messageable_trait()
    {
        // User model uses the default getParticipantDetails from trait
        $this->alpha->name = 'Test User';

        $details = $this->alpha->getParticipantDetails();

        $this->assertIsArray($details);
        $this->assertEquals(['name' => 'Test User'], $details);
    }

    /** @test */
    public function it_returns_custom_participant_details_when_method_is_overridden()
    {
        // Client model has a custom getParticipantDetails method
        $client = factory(\Musonza\Chat\Tests\Helpers\Models\Client::class)->create(['name' => 'Test Client']);

        $details = $client->getParticipantDetails();

        $this->assertIsArray($details);
        $this->assertEquals('Test Client', $details['name']);
        $this->assertEquals('bar', $details['foo']);
    }

    /** @test */
    public function it_can_list_public_conversations_without_participant()
    {
        // Create public conversations
        Chat::createConversation([$this->alpha, $this->bravo])->makePrivate(false);
        Chat::createConversation([$this->alpha, $this->charlie])->makePrivate(false);

        // Create private conversations (should not be included)
        Chat::createConversation([$this->alpha, $this->delta])->makePrivate();

        // Get public conversations without setting a participant
        $publicConversations = Chat::conversations()->isPrivate(false)->get();

        $this->assertCount(2, $publicConversations);
    }

    /** @test */
    public function it_can_list_all_public_conversations_without_participant_even_when_not_member()
    {
        // Create public conversations where user is not a member
        Chat::createConversation([$this->bravo, $this->charlie])->makePrivate(false);
        Chat::createConversation([$this->charlie, $this->delta])->makePrivate(false);

        // Get public conversations without setting a participant
        $publicConversations = Chat::conversations()->isPrivate(false)->get();

        $this->assertCount(2, $publicConversations);
    }

    /** @test */
    public function it_throws_exception_when_listing_private_conversations_without_participant()
    {
        Chat::createConversation([$this->alpha, $this->bravo])->makePrivate();

        $this->expectException(InvalidConversationListException::class);

        // Attempting to list private conversations without a participant should fail
        Chat::conversations()->isPrivate(true)->get();
    }

    /** @test */
    public function it_returns_public_conversations_with_pagination()
    {
        // Create 5 public conversations
        for ($i = 0; $i < 5; $i++) {
            Chat::createConversation([$this->alpha, $this->bravo])->makePrivate(false);
        }

        // Get first page with limit 2
        $firstPage = Chat::conversations()->isPrivate(false)->limit(2)->page(1)->get();
        $this->assertCount(2, $firstPage);

        // Get second page with limit 2
        $secondPage = Chat::conversations()->isPrivate(false)->limit(2)->page(2)->get();
        $this->assertCount(2, $secondPage);
    }

    /** @test */
    public function it_returns_public_conversations_with_last_message_and_participants()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo])->makePrivate(false);
        Chat::message('Hello public world')->from($this->alpha)->to($conversation)->send();

        $publicConversations = Chat::conversations()->isPrivate(false)->get();

        $this->assertCount(1, $publicConversations);
        $this->assertNotNull($publicConversations->first()->last_message);
        $this->assertEquals('Hello public world', $publicConversations->first()->last_message->body);
        $this->assertCount(2, $publicConversations->first()->participants);
    }
}
