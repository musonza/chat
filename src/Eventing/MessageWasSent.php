<?php

namespace Musonza\Chat\Eventing;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Musonza\Chat\Models\Message;
use Musonza\Chat\Models\MessageNotification;

class MessageWasSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;

        $this->createNotifications();
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $order = null;
        $offer = null;
        $sender = null;
        if($this->message->offer && $this->message->offer->order){
            $order = [
                 'id' =>  $this->message->offer->order->id,
                 'number' =>  $this->message->offer->order->number,
                'created_at' =>  $this->message->offer->order->created_at,
                'updated_at' =>  $this->message->offer->order->updated_at,
            ];
        }

        if($this->message->offer){
            $offer = [
                'id' =>  $this->message->offer->id,
                'owner_type' =>  $this->message->offer->owner_type,
                'owner_id' =>  $this->message->offer->owner_id,
                'item_type' =>  $this->message->offer->item_type,
                'item_id' =>  $this->message->offer->item_id,
                'location' =>  $this->message->offer->location,
                'days' =>  $this->message->offer->days,
                'rules' =>  $this->message->offer->rules,
                'deadline_at' =>  $this->message->offer->deadline_at,
                'description' =>  $this->message->offer->description,
                'discount' =>  $this->message->offer->discount,
                'revisions' =>  $this->message->offer->revisions,
                'in_advance' =>  $this->message->offer->in_advance,
                'is_cancelled' =>  $this->message->offer->is_cancelled,
                'is_active' =>  $this->message->offer->is_active,
                'created_at' =>  $this->message->offer->created_at,
                'updated_at' =>  $this->message->offer->updated_at,
                'order' =>  $order,
            ];
        }

        if($this->message->sender){
            $sender = [
                'id' =>  $this->message->sender->id,
                'first_name' =>  $this->message->sender->first_name,
                'last_name' =>  $this->message->sender->last_name,
                'company' =>  $this->message->sender->company,
                'location' =>  $this->message->sender->location,
                'is_online' =>  $this->message->sender->is_online,
                'url' =>  $this->message->sender->url,
                'username' =>  $this->message->sender->username,
            ];
        }
        return ['message' => [
            'id' => $this->message->id,
            'body' => $this->message->body,
            'user_id' => $this->message->user_id,
            'offer_id' => $this->message->offer_id,
            'type' => $this->message->type,
            'created_at' => $this->message->created_at,
            'created_at' => $this->message->updated_at,
            'created_at' => $this->message->updated_at,
            'sender' => $sender,
            'offer' => $offer
        ]];
    }
    /**
     * Creates an entry in the message_notification table for each participant
     * This will be used to determine if a message is read or deleted.
     */
    public function createNotifications()
    {
        MessageNotification::make($this->message, $this->message->conversation);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('mc-chat-conversation.' . $this->message->conversation->id);
    }
}
