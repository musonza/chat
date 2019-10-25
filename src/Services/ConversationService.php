<?php

namespace Musonza\Chat\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message;
use Musonza\Chat\Traits\Paginates;
use Musonza\Chat\Traits\SetsParticipants;

class ConversationService
{
    use SetsParticipants, Paginates;

    protected $isPrivate = null;
    /**
     * @var Conversation
     */
    public $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function start($participants, $data = [])
    {
        return $this->conversation->start($participants, $data);
    }

    public function setConversation($conversation)
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getById($id)
    {
        return $this->conversation->findOrFail($id);
    }

    /**
     * Get messages in a conversation.
     *
     * @return Message
     */
    public function getMessages()
    {
        return $this->conversation->getMessages($this->user, $this->getPaginationParams(), $this->deleted);
    }

    /**
     * Clears conversation.
     */
    public function clear()
    {
        $this->conversation->clear($this->user);
    }

    /**
     * Mark all messages in Conversation as read.
     *
     * @return void
     */
    public function readAll()
    {
        $this->conversation->readAll($this->user);
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
        $conversation1 = $this->conversation->userConversations($participantOne)->toArray();
        $conversation2 = $this->conversation->userConversations($participantTwo)->toArray();

        $common_conversations = $this->getConversationsInCommon($conversation1, $conversation2);

        if (!$common_conversations) {
            return null;
        }

        return $this->conversation->findOrFail($common_conversations[0]);
    }

    /**
     * Get Conversations with latest message.
     *
     * @return LengthAwarePaginator
     */
    public function get()
    {
        return $this->conversation->getUserConversations($this->user, [
          'perPage'   => $this->perPage,
          'page'      => $this->page,
          'pageName'  => 'page',
          'isPrivate' => $this->isPrivate,
        ]);
    }

    /**
     * Add user(s) to a conversation.
     *
     * @param int | array  $userId / array of user ids or an integer
     *
     * @return Conversation
     */
    public function addParticipants($userId)
    {
        return $this->conversation->addParticipants($userId);
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
        return $this->conversation->removeUsers($users);
    }

    /**
     * Get count for unread messages.
     */
    public function unreadCount()
    {
        return $this->conversation->unReadNotifications($this->user)->count();
    }

    /**
     * Gets the conversations in common.
     *
     * @param array $conversation1 The conversations for user one
     * @param array $conversation2 The conversations for user two
     *
     * @return Conversation The conversations in common.
     */
    private function getConversationsInCommon($conversation1, $conversation2)
    {
        return array_values(array_intersect($conversation1, $conversation2));
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
        $this->isPrivate = $isPrivate;

        return $this;
    }
}
