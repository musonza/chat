<?php

namespace Musonza\Chat;

use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\MessageNotification;
use Musonza\Chat\Services\ConversationService;
use Musonza\Chat\Services\MessageService;
use Musonza\Chat\Traits\SetsParticipants;

class Chat
{
    use SetsParticipants;

    /**
     * @param MessageService $messageService
     * @param ConversationService $conversationService
     * @param MessageNotification $messageNotification
     */
    public function __construct(MessageService $messageService, ConversationService $conversationService, MessageNotification $messageNotification)
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
    public function createConversation(array $participants, array $data = [])
    {
        return $this->conversationService->start($participants, $data);
    }

    /**
     * Sets message.
     *
     * @param string | Musonza\Chat\Models\Message  $message
     *
     * @return MessageService
     */
    public function message($message)
    {
        return $this->messageService->setMessage($message);
    }

    /**
     * Gets MessageService.
     *
     * @return MessageService
     */
    public function messages()
    {
        return $this->messageService;
    }

    /**
     * Sets Conversation.
     *
     * @param  Conversation $conversation
     * @return ConversationService
     */
    public function conversation(Conversation $conversation)
    {
        return $this->conversationService->setConversation($conversation);
    }

    /**
     * Gets ConversationService.
     *
     * @return ConversationService
     */
    public function conversations()
    {
        return $this->conversationService;
    }

    /**
     * Get unread notifications.
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

    /**
     * Should the messages be broadcasted.
     *
     * @return boolean
     */
    public static function broadcasts()
    {
        return config('musonza_chat.broadcasts');
    }
}
