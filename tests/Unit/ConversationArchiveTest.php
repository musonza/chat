<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Participation;

class ConversationArchiveTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_participant_can_archive_a_conversation()
    {
        /** @var Conversation $conversation */
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::conversation($conversation)->setParticipant($this->alpha)->archive();

        $alphaParticipation = Chat::conversation($conversation)->getParticipation($this->alpha);
        $bravoParticipation = Chat::conversation($conversation)->getParticipation($this->bravo);

        $this->assertNotNull($alphaParticipation->archived_at);
        $this->assertTrue($alphaParticipation->isArchived());
        $this->assertNull($bravoParticipation->archived_at, 'Archive must not bleed into other participants');
    }

    public function test_archiving_is_idempotent()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::conversation($conversation)->setParticipant($this->alpha)->archive();
        $first = Chat::conversation($conversation)->getParticipation($this->alpha);

        // Re-archive should be a no-op: neither archived_at nor updated_at should move.
        Chat::conversation($conversation)->setParticipant($this->alpha)->archive();
        $second = Chat::conversation($conversation)->getParticipation($this->alpha);

        $this->assertTrue(
            $first->archived_at->equalTo($second->archived_at),
            'archived_at must not change on a no-op re-archive'
        );
        $this->assertTrue(
            $first->updated_at->equalTo($second->updated_at),
            'updated_at must not change on a no-op re-archive (the row should not be re-saved)'
        );
    }

    public function test_unarchive_restores_a_conversation()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::conversation($conversation)->setParticipant($this->alpha)->archive();
        Chat::conversation($conversation)->setParticipant($this->alpha)->unarchive();

        $this->assertNull(
            Chat::conversation($conversation)->getParticipation($this->alpha)->archived_at
        );
    }

    public function test_archived_conversations_are_excluded_from_default_listing()
    {
        Chat::createConversation([$this->alpha, $this->bravo]);
        $second = Chat::createConversation([$this->alpha, $this->charlie]);
        Chat::createConversation([$this->alpha, $this->delta]);

        Chat::conversation($second)->setParticipant($this->alpha)->archive();

        $list = Chat::conversations()->setParticipant($this->alpha)->get();

        $this->assertCount(2, $list);
        $this->assertNotContains($second->id, $list->pluck('conversation_id')->all());
    }

    public function test_archived_filter_returns_only_archived()
    {
        Chat::createConversation([$this->alpha, $this->bravo]);
        $archivedConv = Chat::createConversation([$this->alpha, $this->charlie]);

        Chat::conversation($archivedConv)->setParticipant($this->alpha)->archive();

        $list = Chat::conversations()->setParticipant($this->alpha)->archived()->get();

        $this->assertCount(1, $list);
        $this->assertEquals($archivedConv->id, $list->first()->conversation_id);
    }

    public function test_with_archived_returns_both_archived_and_non_archived()
    {
        Chat::createConversation([$this->alpha, $this->bravo]);
        $archivedConv = Chat::createConversation([$this->alpha, $this->charlie]);
        Chat::createConversation([$this->alpha, $this->delta]);

        Chat::conversation($archivedConv)->setParticipant($this->alpha)->archive();

        $list = Chat::conversations()->setParticipant($this->alpha)->withArchived()->get();

        $this->assertCount(3, $list);
    }

    public function test_archive_does_not_affect_other_participants_listings()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::conversation($conversation)->setParticipant($this->alpha)->archive();

        // Bravo still sees it in their default inbox
        $bravoList = Chat::conversations()->setParticipant($this->bravo)->get();
        $this->assertCount(1, $bravoList);
        $this->assertEquals($conversation->id, $bravoList->first()->conversation_id);
    }

    public function test_new_message_auto_unarchives_recipient_by_default()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::conversation($conversation)->setParticipant($this->alpha)->archive();

        Chat::message('hi alpha')->from($this->bravo)->to($conversation)->send();

        $alphaParticipation = Chat::conversation($conversation)->getParticipation($this->alpha);
        $this->assertNull($alphaParticipation->archived_at, 'Recipient should be auto-unarchived on new message');
    }

    public function test_auto_unarchive_does_not_affect_sender()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::conversation($conversation)->setParticipant($this->alpha)->archive();

        // Alpha (the archiver) sends a message themselves; their archive should remain.
        Chat::message('still archived from my side')->from($this->alpha)->to($conversation)->send();

        $alphaParticipation = Chat::conversation($conversation)->getParticipation($this->alpha);
        $this->assertNotNull($alphaParticipation->archived_at, 'Sender archive should not be cleared by their own message');
    }

    public function test_auto_unarchive_can_be_disabled_via_config()
    {
        $this->app['config']->set('musonza_chat.unarchive_on_new_message', false);

        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        Chat::conversation($conversation)->setParticipant($this->alpha)->archive();

        Chat::message('hi alpha')->from($this->bravo)->to($conversation)->send();

        $alphaParticipation = Chat::conversation($conversation)->getParticipation($this->alpha);
        $this->assertNotNull($alphaParticipation->archived_at, 'Recipient must remain archived when feature is disabled');
    }

    public function test_archive_no_op_for_non_participant()
    {
        $conversation = Chat::createConversation([$this->alpha, $this->bravo]);

        // charlie is not a participant — must not throw and must not insert a row
        $conversation->archive($this->charlie);

        $this->assertEquals(2, Participation::where('conversation_id', $conversation->id)->count());
    }

    public function test_participation_scopes()
    {
        $conv1 = Chat::createConversation([$this->alpha, $this->bravo]);
        Chat::createConversation([$this->alpha, $this->charlie]);

        Chat::conversation($conv1)->setParticipant($this->alpha)->archive();

        $this->assertEquals(1, Participation::query()->archived()
            ->where('messageable_id', $this->alpha->getKey())
            ->where('messageable_type', $this->alpha->getMorphClass())
            ->count());

        $this->assertEquals(1, Participation::query()->notArchived()
            ->where('messageable_id', $this->alpha->getKey())
            ->where('messageable_type', $this->alpha->getMorphClass())
            ->count());
    }
}
