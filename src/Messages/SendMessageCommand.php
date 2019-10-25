<?php

namespace Musonza\Chat\Messages;

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Models\Conversation;

class SendMessageCommand
{
    public $senderId;
    public $body;
    public $conversation;
    public $type;
    public $senderType;

    /**
     * @param Conversation $conversation The conversation
     * @param string       $body         The message body
     * @param Model        $sender       The sender identifier
     * @param string       $type         The message type
     */
    public function __construct(Conversation $conversation, $body, Model $sender, $type = 'text')
    {
        $this->conversation = $conversation;
        $this->body = $body;
        $this->type = $type;
        $this->senderId = $sender->getKey();
        $this->senderType = get_class($sender);
    }
}
