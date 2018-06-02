<?php

namespace Musonza\Chat;

use Musonza\Chat\Traits\IdentifiesUsers;
use Musonza\Chat\Services\MessageService;
use Musonza\Chat\Models\MessageNotification;
use Musonza\Chat\Services\ConversationService;

class Chat
{
    use IdentifiesUsers;

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
     * @return Musonza\Chat\Messages\Message
     */
    public function message($message)
    {
        return $this->messageService->setMessage($message);
    }

    public function messages()
    {
        return $this->messageService;
    }

    public function conversation($conversation)
    {
        return $this->conversationService->setConversation($conversation);
    }

    public function conversations()
    {
        return $this->conversationService;
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
