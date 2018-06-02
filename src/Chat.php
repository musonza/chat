<?php

namespace Musonza\Chat;

use Musonza\Chat\Traits\Paginates;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Traits\IdentifiesUsers;
use Musonza\Chat\Services\MessageService;
use Musonza\Chat\Models\MessageNotification;
use Musonza\Chat\Services\ConversationService;

class Chat
{
    use IdentifiesUsers, Paginates;

    /**
     * @param Conversation $conversation The conversation
     * @param MessageNotification      $messageNotification   Notifications
     */
    public function __construct(
        MessageService $messageService,
        ConversationService $conversationService,
        MessageNotification $messageNotification)
    {
        $this->messageService = $messageService;
        $this->conversationService = $conversationService;
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
        return $this->conversationService->start($participants);
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
    public function getConversation($conversationId)
    {
        return $this->conversationService->getById($conversationId);
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

    public function conversation($conversation)
    {
        return $this->conversationService->setConversation($conversation);
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

    public function conversations(Conversation $conversation = null)
    {
        if (!$conversation) {
            return $this->conversationService;
        }

        //return $this->conversationService;

       // $this->conversation = $conversation;

        return $this;
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
