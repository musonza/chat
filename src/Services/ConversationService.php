<?php

namespace Musonza\Chat\Services;

use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message;
use Musonza\Chat\Traits\Paginates;
use Musonza\Chat\Traits\SetsParticipants;

class ConversationService
{
    use SetsParticipants, Paginates;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function start($participants)
    {
        return $this->conversation->start($participants);
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
     * @param int | User $userOne
     * @param int | User $userTwo
     *
     * @return Conversation
     */
    public function between($userOne, $userTwo)
    {
        $conversation1 = $this->conversation->userConversations($userOne)->toArray();
        $conversation2 = $this->conversation->userConversations($userTwo)->toArray();

        $common_conversations = $this->getConversationsInCommon($conversation1, $conversation2);

        if (!$common_conversations) {
            return;
        }

        return $this->conversation->findOrFail($common_conversations[0]);
    }

    /**
     * Get conversations that users have in common.
     *
     *  @param array | collection $users
     *
     * @return Conversations
     */
    public function common($users)
    {
        return $this->conversation->common($users);
    }

    /**
     * Get Conversations with lastest message.
     *
     * @param object $user
     *
     * @return Illuminate\Pagination\LengthAwarePaginator
     */
    public function get()
    {
        return $this->conversation->getList($this->user, $this->perPage, $this->page, $pageName = 'page');
    }

    /**
     * Add user(s) to a conversation.
     *
     * @param Conversation $conversation
     * @param int | array  $userId       / array of user ids or an integer
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
     * @param Conversation $conversation
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
     *
     * @return void
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
}
