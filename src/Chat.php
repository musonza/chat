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
     * @var MessageService
     */
    protected $messageService;

    /**
     * @var ConversationService
     */
    protected $conversationService;

    /**
     * @var MessageNotification
     */
    protected $messageNotification;

    public function __construct(
        MessageService $messageService,
        ConversationService $conversationService,
        MessageNotification $messageNotification
    ) {
        $this->messageService      = $messageService;
        $this->conversationService = $conversationService;
        $this->messageNotification = $messageNotification;
    }

    /**
     * Creates a new conversation.
     *
     *
     * @return Conversation
     */
    public function createConversation(array $participants, array $data = [])
    {
        $payload = [
            'participants'   => $participants,
            'data'           => $data,
            'direct_message' => $this->conversationService->directMessage,
        ];

        return $this->conversationService->start($payload);
    }

    public function makeDirect()
    {
        $this->conversationService->directMessage = true;

        return $this;
    }

    /**
     * Sets message.
     *
     * @param  string  $message
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
     *
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
        return $this->messageNotification->unReadNotifications($this->participant);
    }

    /**
     * Should the messages be broadcasted.
     *
     * @return bool
     */
    public static function broadcasts()
    {
        return config('musonza_chat.broadcasts');
    }

    public static function sentMessageEvent()
    {
        return config('musonza_chat.sent_message_event');
    }

    public static function senderFieldsWhitelist()
    {
        $fields = config('musonza_chat.sender_fields_whitelist', []);

        return (is_array($fields) && ! empty($fields)) ? $fields : null;
    }

    /**
     * Check if message encryption is enabled.
     */
    public static function shouldEncryptMessages(): bool
    {
        return (bool) config('musonza_chat.encrypt_messages', false);
    }
}
