<?php

namespace Musonza\Chat\Notifications;

use Illuminate\Support\Facades\Notification;
use Musonza\Chat\Conversations\Conversation;
use Eloquent;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Musonza\Chat\Messages\Message;
use Musonza\Chat\Chat;

class MessageNotification extends Eloquent
{
    // use SoftDeletes;

    protected $fillable = ['user_id', 'message_id', 'conversation_id'];

    protected $table = 'message_notification';

    protected $dates = ['deleted_at'];
    
    /**
     * Creates a new notification.
     *
     * @param Message      $message
     * @param Conversation $conversation
     */
    public static function make(Message $message, Conversation $conversation)
    {
        if (Chat::laravelNotifications()){
            self::createLaravelNotifications($message, $conversation);
        }else{
            self::createCustomNotifications($message, $conversation);
        }
    }

    public static function createCustomNotifications($message, $conversation)
    {
        $notification = [];

        foreach ($conversation->users as $user) {

            $is_sender = ($message->user_id == $user->id) ? 1 : 0;

            $notification[] = [
                'user_id' => $user->id,
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'is_seen' => $is_sender,
                'is_sender' => $is_sender,
                'created_at' => $message->created_at,
            ];
        }

        MessageNotification::insert($notification);
    }

    public static function createLaravelNotifications($message, $conversation)
    {
        $recipients = $conversation->users->filter(function ($user) use ($message, $conversation) {
            if ($message->user_id === $user->id) {
                $user->notify(new MessageSent([
                    'message_id' => $message->id,
                    'conversation_id' => $conversation->id,
                    'outgoing' => true,
                ]));
            }

            return $message->user_id !== $user->id;
        });

        Notification::send($recipients, new MessageSent([
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
        ]));
    }

    public function markAsRead()
    {
       $this->is_seen = 1;
       $this->update(['is_seen' => 1]);
       $this->save();
    }

    public static function readAll($notifications)
    {
        dd($notifications->toArray());
    }
}
