<?php

namespace Musonza\Chat\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Musonza\Chat\Eventing\ConversationStarted;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Traits\Paginates;
use Musonza\Chat\Traits\SetsParticipants;

class ConversationService
{
    use SetsParticipants;
    use Paginates;
    protected $filters = [];

    /**
     * @var Conversation
     */
    public $conversation;

    public $directMessage = false;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function start(array $payload)
    {
        $conversation = $this->conversation->start($payload);

        event(new ConversationStarted($conversation));

        return $conversation;
    }

    public function setConversation($conversation)
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getById($id)
    {
        return $this->conversation->find($id);
    }

    /**
     * Get messages in a conversation.
     */
    public function getMessages()
    {
        return $this->conversation->getMessages($this->participant, $this->getPaginationParams(), $this->deleted);
    }

    /**
     * Clears conversation.
     */
    public function clear()
    {
        $this->conversation->clear($this->participant);
    }

    /**
     * Mark all messages in Conversation as read.
     *
     * @return void
     */
    public function readAll()
    {
        $this->conversation->readAll($this->participant);
    }

    /**
     * Get Private Conversation between two users.
     *
     * @param Model $participantOne
     * @param Model $participantTwo
     *
     * @return Conversation
     */
    public function between(Model $participantOne, Model $participantTwo)
    {
        $participantOneConversationIds = $this->conversation
            ->participantConversations($participantOne, true)
            ->pluck('id');

        $participantTwoConversationIds = $this->conversation
            ->participantConversations($participantTwo, true)
            ->pluck('id');

        $common = $this->getConversationsInCommon($participantOneConversationIds, $participantTwoConversationIds);

        return $common ? $this->conversation->findOrFail($common[0]) : null;
    }

    /**
     * Get Conversations with latest message.
     *
     * @return LengthAwarePaginator
     */
    public function get()
    {
        return $this->conversation->getParticipantConversations($this->participant, [
            'perPage'   => $this->perPage,
            'page'      => $this->page,
            'pageName'  => 'page',
            'filters'   => $this->filters,
        ]);
    }

    /**
     * Add user(s) to a conversation.
     *
     * @param array $participants
     *
     * @return Conversation
     */
    public function addParticipants(array $participants)
    {
        return $this->conversation->addParticipants($participants);
    }

    /**
     * Remove user(s) from a conversation.
     *
     * @param $users / array of user ids or an integer
     *
     * @return Conversation
     */
    public function removeParticipants($users)
    {
        return $this->conversation->removeParticipant($users);
    }

    /**
     * Get count for unread messages.
     *
     * @return int
     */
    public function unreadCount()
    {
        return $this->conversation->unReadNotifications($this->participant)->count();
    }

    /**
     * Gets the conversations in common.
     *
     * @param Collection $conversation1 The conversation Ids for user one
     * @param Collection $conversation2 The conversation Ids for user two
     *
     * @return Conversation The conversations in common.
     */
    private function getConversationsInCommon(Collection $conversation1, Collection $conversation2)
    {
        return array_values(array_intersect($conversation1->toArray(), $conversation2->toArray()));
    }

    /**
     * Sets the conversation type to query for, public or private.
     *
     * @param bool $isPrivate
     *
     * @return $this
     */
    public function isPrivate($isPrivate = true)
    {
        $this->filters['private'] = $isPrivate;

        return $this;
    }

    /**
     * Sets the conversation type to query for direct conversations.
     *
     * @param bool $isDirectMessage
     *
     * @return $this
     */
    public function isDirect($isDirectMessage = true)
    {
        $this->filters['direct_message'] = $isDirectMessage;

        // Direct messages are always private
        $this->filters['private'] = true;

        return $this;
    }

    public function getParticipation($participant = null)
    {
        $participant = $participant ?? $this->participant;

        return $participant->participation()->first();
    }
}
