<?php

namespace Musonza\Chat\Eventing;

use Musonza\Chat\Models\Conversation;

class AllParticipantsClearedConversation
{
    public $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }
}
