<?php

namespace Musonza\Chat\Messages;

use Musonza\Chat\Notifications\MessageNotification;

class MessageWasSent
{
    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;

        $this->createNotifications();
    }

    /**
     * Creates an entry in the message_notification table for each participant
     * This will be used to determine if a message is read or deleted.
     */
    public function createNotifications()
    {
        MessageNotification::make($this->message, $this->message->conversation);
    }
}
