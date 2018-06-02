<?php

namespace Musonza\Chat\Messages;

use Musonza\Chat\Models\Message as MessageModel;

class Message
{
    protected $type = 'text';
    protected $from;
    protected $to;
    protected $body;

    public function __construct($message)
    {
        dd($message);
    }

    public function from()
    {
        return $this;
    }

    public function to()
    {
        return $this;
    }

    public function for()
    {
        return $this;
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
