<?php

namespace Musonza\Chat\Eventing;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Musonza\Chat\Models\Reaction;

class MessageReactionAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Reaction
     */
    public $reaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Reaction $reaction)
    {
        $this->reaction = $reaction;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('mc-chat-conversation.'.$this->reaction->message->conversation_id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'reaction' => [
                'id'         => $this->reaction->id,
                'message_id' => $this->reaction->message_id,
                'reaction'   => $this->reaction->reaction,
                'user'       => [
                    'id'   => $this->reaction->messageable_id,
                    'type' => $this->reaction->messageable_type,
                ],
            ],
        ];
    }
}
