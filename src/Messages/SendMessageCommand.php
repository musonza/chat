<?php

namespace Musonza\Chat\Messages;

use Musonza\Chat\Models\Conversation;

class SendMessageCommand
{
    public $senderId;
    public $offerId;
    public $body;
    public $conversation;

    /**
     * @param Conversation $conversation The conversation
     * @param string $body The message body
     * @param int $senderId The sender identifier
     * @param string $type The message type
     */
    public function __construct(Conversation $conversation, $body, $senderId, $offerId, $type = 'text')
    {
        $this->conversation = $conversation;
        $this->body = $body;
        $this->type = $type;
        $this->offerId = $offerId;
        $this->senderId = $senderId;
    }
}
