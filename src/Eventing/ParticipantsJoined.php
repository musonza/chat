<?php

namespace Musonza\Chat\Eventing;

use Musonza\Chat\Models\Conversation;

class ParticipantsJoined extends Event
{
    /**
     * @var Conversation
     */
    public $conversation;

    public $participants;

    public function __construct(Conversation $conversation, $participants)
    {
        $this->conversation = $conversation;
        $this->participants = $participants;
    }
}
