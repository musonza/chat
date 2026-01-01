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
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

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
        return new PrivateChannel('mc-chat-conversation.' . $this->message->conversation_id);
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id'              => $this->message->getKey(),
                'body'            => $this->message->body,
                'conversation_id' => $this->message->conversation_id,
                'type'            => $this->message->type,
                'data'            => $this->message->data,
                'created_at'      => $this->message->created_at,
                'sender'          => $this->message->sender,
            ],
        ];
    }
}
