<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Musonza\Chat\Models\Reaction;

class ReactionTest extends TestCase
{
    use DatabaseMigrations;
    public function test_it_can_add_a_reaction_to_a_message()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        $reaction = Chat::message($message)->setParticipant($this->bravo)->react('👍');

        $this->assertInstanceOf(Reaction::class, $reaction);
        $this->assertEquals('👍', $reaction->reaction);
        $this->assertEquals($this->bravo->getKey(), $reaction->messageable_id);
    }
    public function test_it_can_add_multiple_different_reactions_to_a_message()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        Chat::message($message)->setParticipant($this->bravo)->react('👍');
        Chat::message($message)->setParticipant($this->bravo)->react('❤️');
        Chat::message($message)->setParticipant($this->alpha)->react('👍');

        $this->assertEquals(3, $message->reactions()->count());
    }
    public function test_it_does_not_duplicate_same_reaction_from_same_user()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        Chat::message($message)->setParticipant($this->bravo)->react('👍');
        Chat::message($message)->setParticipant($this->bravo)->react('👍');

        $this->assertEquals(1, $message->reactions()->count());
    }
    public function test_it_can_remove_a_reaction_from_a_message()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        Chat::message($message)->setParticipant($this->bravo)->react('👍');
        $this->assertEquals(1, $message->reactions()->count());

        $removed = Chat::message($message)->setParticipant($this->bravo)->unreact('👍');

        $this->assertTrue($removed);
        $this->assertEquals(0, $message->reactions()->count());
    }
    public function test_it_returns_false_when_removing_nonexistent_reaction()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        $removed = Chat::message($message)->setParticipant($this->bravo)->unreact('👍');

        $this->assertFalse($removed);
    }
    public function test_it_can_toggle_a_reaction()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        // First toggle adds the reaction
        $result = Chat::message($message)->setParticipant($this->bravo)->toggleReaction('👍');
        $this->assertTrue($result['added']);
        $this->assertInstanceOf(Reaction::class, $result['reaction']);

        // Second toggle removes it
        $result = Chat::message($message)->setParticipant($this->bravo)->toggleReaction('👍');
        $this->assertFalse($result['added']);
        $this->assertNull($result['reaction']);
    }
    public function test_it_can_get_reactions_summary()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo, $this->charlie]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        Chat::message($message)->setParticipant($this->bravo)->react('👍');
        Chat::message($message)->setParticipant($this->charlie)->react('👍');
        Chat::message($message)->setParticipant($this->bravo)->react('❤️');

        $summary = Chat::message($message)->reactionsSummary();

        $this->assertEquals(2, $summary['👍']);
        $this->assertEquals(1, $summary['❤️']);
    }
    public function test_it_can_check_if_participant_has_reacted()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        Chat::message($message)->setParticipant($this->bravo)->react('👍');

        $this->assertTrue(Chat::message($message)->setParticipant($this->bravo)->hasReacted('👍'));
        $this->assertFalse(Chat::message($message)->setParticipant($this->bravo)->hasReacted('❤️'));
        $this->assertTrue(Chat::message($message)->setParticipant($this->bravo)->hasReacted()); // any reaction
        $this->assertFalse(Chat::message($message)->setParticipant($this->alpha)->hasReacted());
    }
    public function test_it_can_get_all_reactions_on_a_message()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        Chat::message($message)->setParticipant($this->bravo)->react('👍');
        Chat::message($message)->setParticipant($this->bravo)->react('❤️');

        $reactions = Chat::message($message)->reactions();

        $this->assertEquals(2, $reactions->count());
    }
    public function test_it_can_use_text_based_reactions()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        $reaction = Chat::message($message)->setParticipant($this->bravo)->react('like');

        $this->assertEquals('like', $reaction->reaction);
    }
    public function test_reactions_belong_to_message()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        $reaction = Chat::message($message)->setParticipant($this->bravo)->react('👍');

        $this->assertEquals($message->id, $reaction->message->id);
        $this->assertEquals($message->id, $reaction->message_id);
    }
    public function test_it_can_get_reactions_by_participant()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);
        $message      = Chat::message('Hello')->from($this->alpha)->to($conversation)->send();

        $message->react($this->bravo, '👍');
        $message->react($this->bravo, '❤️');
        $message->react($this->alpha, '👍');

        $bravoReactions = $message->getReactionsByParticipant($this->bravo);

        $this->assertEquals(2, $bravoReactions->count());
    }
}
