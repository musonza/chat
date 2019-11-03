<?php

namespace Musonza\Chat\Eventing;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Musonza\Chat\Models\Message;

class MessageWasSent extends Event implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('mc-chat-conversation.'.$this->message->conversation_id);
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id'              => $this->message->id,
                'body'            => $this->message->body,
                'conversation_id' => $this->message->conversation_id,
                'type'            => $this->message->type,
                'created_at'      => $this->message->created_at,
                'sender'          => [
                    'id'               => $this->message->sender->id,
                    'name'             => $this->message->sender->name,
                    'messageable_type' => $this->message->sender->participation[0]->messageable_type,
                    'participation_id' => $this->message->sender->participation[0]->id,
                ],
            ],
        ];
    }
}
