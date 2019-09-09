<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Musonza\Chat\BaseModel;

class MessageNotification extends BaseModel
{
    use SoftDeletes;

    protected $fillable = ['messageable_id', 'messageable_type', 'message_id', 'conversation_id'];
    protected $table = 'mc_message_notification';
    protected $dates = ['deleted_at'];

    /**
     * Creates a new notification.
     *
     * @param Message      $message
     * @param Conversation $conversation
     */
    public static function make(Message $message, Conversation $conversation)
    {
        self::createCustomNotifications($message, $conversation);
    }

    public function unReadNotifications(Model $participant)
    {
        return self::where([
            ['messageable_id', '=', $participant->getKey()],
            ['messageable_type', '=', get_class($participant)],
            ['is_seen', '=', 0],
        ])->get();
    }

    public static function createCustomNotifications($message, $conversation)
    {
        $notification = [];

        foreach ($conversation->participants as $participation) {
            $is_sender = ($message->participation_id == $participation->id) ? 1 : 0;

            $notification[] = [
                'messageable_id'   => $participation->messageable_id,
                'messageable_type' => $participation->messageable_type,
                'message_id'       => $message->id,
                'participation_id' => $participation->id,
                'conversation_id'  => $conversation->id,
                'is_seen'          => $is_sender,
                'is_sender'        => $is_sender,
                'created_at'       => $message->created_at,
            ];
        }

        self::insert($notification);
    }

    public function markAsRead()
    {
        $this->is_seen = 1;
        $this->update(['is_seen' => 1]);
        $this->save();
    }
}
