<?php

namespace Musonza\Chat\Eventing;

use Musonza\Chat\Models\Message;

class AllMessagesDeleted extends Event
{
    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }
}