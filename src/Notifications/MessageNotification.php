<?php

namespace Musonza\Chat\Notifications;

use Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Musonza\Chat\Chat;
use Musonza\Chat\Conversations\Conversation;
use Musonza\Chat\Messages\Message;

class MessageNotification extends Eloquent
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'message_id', 'conversation_id'];

    protected $table = 'message_notification';

    protected $dates = ['deleted_at'];

    public function sender()
    {
        return $this->belongsTo(Chat::userModel(), 'user_id');
    }

    public function message()
    {
        return $this->belongsTo('Musonza\Chat\Messages\Message', 'message_id');
    }

    /**
     * Creates a new notification.
     *
     * @param Message      $message
     * @param Conversation $conversation
     */
    public static function make(Message $message, Conversation $conversation)
    {
        $notification = [];

        foreach ($conversation->users as $user) {
            $is_sender = ($message->user_id == $user->id) ? 1 : 0;

            $notification[] = [
                'user_id'         => $user->id,
                'message_id'      => $message->id,
                'conversation_id' => $conversation->id,
                'is_seen'         => $is_sender,
                'is_sender'       => $is_sender,
                'created_at'      => $message->created_at,
            ];
        }

        self::insert($notification);
    }
}
