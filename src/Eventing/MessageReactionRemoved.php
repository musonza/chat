<?php

namespace Musonza\Chat\Eventing;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReactionRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var int
     */
    public $messageId;

    /**
     * @var int
     */
    public $conversationId;

    /**
     * @var string
     */
    public $reaction;

    /**
     * @var int
     */
    public $messageableId;

    /**
     * @var string
     */
    public $messageableType;

    /**
     * Create a new event instance.
     */
    public function __construct(int $messageId, int $conversationId, string $reaction, int $messageableId, string $messageableType)
    {
        $this->messageId       = $messageId;
        $this->conversationId  = $conversationId;
        $this->reaction        = $reaction;
        $this->messageableId   = $messageableId;
        $this->messageableType = $messageableType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('mc-chat-conversation.' . $this->conversationId);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'reaction'   => $this->reaction,
            'user' => [
                'id'   => $this->messageableId,
                'type' => $this->messageableType,
            ],
        ];
    }
}
