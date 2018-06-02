<?php

namespace Musonza\Chat\Services;

use Musonza\Chat\Commanding\CommandBus;
use Musonza\Chat\Messages\SendMessageCommand;
use Musonza\Chat\Models\Message;
use Musonza\Chat\Traits\SetsParticipants;

class MessageService
{
    use SetsParticipants;

    protected $type = 'text';
    protected $body;

    public function __construct(CommandBus $commandBus, Message $message)
    {
        $this->commandBus = $commandBus;
        $this->message = $message;
    }

    public function setMessage($message)
    {
        if (is_object($message)) {
            $this->message = $message;
        } else {
            $this->body = $message;
        }

        return $this;
    }

    /**
     * Set Message type.
     *
     * @param string type
     *
     * @return $this
     */
    public function type(String $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getById($id)
    {
        return $this->message->findOrFail($id);
    }

    /**
     * Mark a message as read.
     *
     * @return void
     */
    public function markRead()
    {
        $this->message->markRead($this->user);
    }

    /**
     * Deletes message.
     *
     * @return void
     */
    public function delete()
    {
        $this->message->trash($this->user);
    }

    /**
     * Get count for unread messages.
     *
     * @return void
     */
    public function unreadCount()
    {
        return $this->message->unreadCount($this->user);
    }

    public function toggleFlag()
    {
        return $this->message->toggleFlag($this->user);
    }

    public function flagged()
    {
        return $this->message->flagged($this->user);
    }

    /**
     * Sends the message.
     *
     * @return void
     */
    public function send()
    {
        if (!$this->from) {
            throw new \Exception('Message sender has not been set');
        }

        if (!$this->body) {
            throw new \Exception('Message body has not been set');
        }

        if (!$this->to) {
            throw new \Exception('Message receiver has not been set');
        }

        $command = new SendMessageCommand($this->to, $this->body, $this->from, $this->type);

        return $this->commandBus->execute($command);
    }
}
