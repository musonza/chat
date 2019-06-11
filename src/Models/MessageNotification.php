<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Notification;
use Musonza\Chat\BaseModel;

class MessageNotification extends BaseModel
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'message_id', 'conversation_id'];
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

    public function unReadNotifications($user)
    {
        return self::where([
            ['user_id', '=', $user->getKey()],
            ['is_seen', '=', 0],
        ])->get();
    }

    public static function createCustomNotifications($message, $conversation)
    {
        $notification = [];

        foreach ($conversation->users as $user) {
            $is_sender = ($message->user_id == $user->getKey()) ? 1 : 0;

            $notification[] = [
                'user_id'         => $user->getKey(),
                'message_id'      => $message->id,
                'conversation_id' => $conversation->id,
                'is_seen'         => $is_sender,
                'is_sender'       => $is_sender,
                'created_at'      => $message->created_at,
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
