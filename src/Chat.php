<?php

namespace Musonza\Chat;

use Musonza\Chat\Traits\Paginates;
use Musonza\Chat\Services\MessageService;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Commanding\CommandBus;
use Musonza\Chat\Traits\IdentifiesUsers;
use Musonza\Chat\Models\MessageNotification;
use Musonza\Chat\Messages\SendMessageCommand;

class Chat
{
    use IdentifiesUsers, Paginates;

    protected $deleted = false;

    /**
     * @param Conversation $conversation The conversation
     * @param CommandBus      $commandBus   The command bus
     * @param MessageNotification      $messageNotification   Notifications
     */
    public function __construct(
        MessageService $messageService,
        Conversation $conversation,
        CommandBus $commandBus,
        MessageNotification $messageNotification)
    {
        $this->messageService = $messageService;
        $this->conversation = $conversation;
        $this->commandBus = $commandBus;
        $this->messageNotification = $messageNotification;
    }

    /**
     * Creates a new conversation.
     *
     * @param array $participants
     * @param array $data
     *
     * @return Conversation
     */
    public function createConversation(array $participants, array $data = null)
    {
        return $this->conversation->start($participants);
    }

    /**
     * Sets message.
     *
     * @param string | Musonza\Chat\Models\Message  $message
     *
     * @return Musonza\Chat\Messages\Message
     */
    public function message($message)
    {
        return $this->messageService->setMessage($message);
    }

    /**
     * Returns a conversation.
     *
     * @param int $conversationId
     *
     * @return Conversation
     */
    public function conversation($conversationId)
    {
        return $this->conversation->findOrFail($conversationId);
    }

    /**
     * Returns a message.
     *
     * @param int $messageId
     *
     * @return Message
     */
    public function getMessage($messageId)
    {
        return $this->messageService->getById($messageId);
    }

    /**
     * Add user(s) to a conversation.
     *
     * @param Conversation $conversation
     * @param int | array  $userId       / array of user ids or an integer
     *
     * @return Conversation
     */
    public function addParticipants(Conversation $conversation, $userId)
    {
        $conversation->addParticipants($userId);
    }

    public function messages()
    {
        return $this->messageService;
    }

    public function deleted()
    {
        $this->deleted = true;

        return $this;
    }

    /**
     * Remove user(s) from a conversation.
     *
     * @param Conversation $conversation
     * @param $users / array of user ids or an integer
     *
     * @return Conversation
     */
    public function removeParticipants($conversation, $users)
    {
        return $conversation->removeUsers($users);
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

    public function conversations(Conversation $conversation = null)
    {
        $this->conversation = $conversation ? $conversation : $this->conversation;

        return $this;
    }

    /**
     * Get messages in a conversation.
     *
     * @param int $perPage
     * @param int $page
     *
     * @return Message
     */
    public function getMessages($perPage = null, $page = null)
    {
        return $this->conversation->getMessages($this->user, $this->getPaginationParams(), $this->deleted);
    }

    /**
     * Get messages by id.
     *
     * @param int $id
     *
     * @return Message
     */
    public function messageById($id)
    {
        return $this->messageService->getById($id);
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
     * Get conversations that users have in common.
     *
     *  @param array | collection $users
     *
     * @return Conversations
     */
    public function commonConversations($users)
    {
        return $this->conversation->common($users);
    }

    /**
     * Get Private Conversation between two users.
     *
     * @param int | User $userOne
     * @param int | User $userTwo
     *
     * @return Conversation
     */
    public function getConversationBetween($userOne, $userTwo)
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
     * Get unread notifications
     *
     * @return MessageNotification
     */
    public function unReadNotifications()
    {
        return $this->messageNotification->unReadNotifications($this->user);
    }

    /**
     * Returns the User Model class.
     *
     * @return string
     */
    public static function userModel()
    {
        return config('musonza_chat.user_model');
    }

    public static function broadcasts()
    {
        return config('musonza_chat.broadcasts');
    }
}
