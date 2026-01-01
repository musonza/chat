<?php

namespace Musonza\Chat\Messages;

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Eventing\EventDispatcher;
use Musonza\Chat\Models\Message;

class SendMessageCommandHandler
{
    protected $message;

    protected $dispatcher;

    /**
     * @param  EventDispatcher  $dispatcher  The dispatcher
     * @param  Message  $message  The message
     */
    public function __construct(EventDispatcher $dispatcher, Message $message)
    {
        $this->dispatcher = $dispatcher;
        $this->message    = $message;
    }

    /**
     * Triggers sending the message.
     *
     * @param  SendMessageCommand  $command  The command
     * @return Model
     */
    public function handle(SendMessageCommand $command)
    {
        $message = $this->message->send($command->conversation, $command->body, $command->participant, $command->type, $command->data);

        $this->dispatcher->dispatch($this->message->releaseEvents());

        return $message;
    }
}
