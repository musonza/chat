<?php

namespace Musonza\Chat\Messages;

use Eloquent;
use Illuminate\Notifications\Notification;
use Musonza\Chat\Chat;
use Musonza\Chat\Conversations\Conversation;
use Musonza\Chat\Eventing\EventGenerator;
use Musonza\Chat\Notifications\MessageNotification;

class Message extends Eloquent
{
    protected $fillable = ['body', 'user_id', 'type'];

    protected $table = 'mc_messages';

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['conversation'];

    use EventGenerator;

    public function sender()
    {
        return $this->belongsTo(Chat::userModel(), 'user_id');
    }

    public function unreadCount($user)
    {
        if (Chat::laravelNotifications()) {
            return \DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('type', 'Musonza\Chat\Notifications\MessageSent')
                ->whereNull('read_at')
                ->whereNull('notifications.data->outgoing')
                ->count();
        }

        return MessageNotification::where('user_id', $user->id)
            ->where('is_seen', 0)
            ->count();
    }

    public function conversation()
    {
        return $this->belongsTo('Musonza\Chat\Conversations\Conversation', 'conversation_id');
    }

    /**
     * Adds a message to a conversation.
     *
     * @param Conversation $conversation
     * @param string       $body
     * @param int          $userId
     * @param string       $type
     *
     * @return Message
     */
    public function send(Conversation $conversation, $body, $userId, $type = 'text')
    {
        $message = $conversation->messages()->create([
            'body'    => $body,
            'user_id' => $userId,
            'type'    => $type,
        ]);

        $this->raise(new MessageWasSent($message));

        return $message;
    }

    /**
     * Deletes a message.
     *
     * @param Message $message
     * @param User    $user
     *
     * @return
     */
    public function trash($user)
    {
        if (Chat::laravelNotifications()) {
            return $user->notifications()
                ->where('data->message_id', $this->id)
                ->delete();
        }

        return MessageNotification::where('user_id', $user->id)
            ->where('message_id', $this->id)
            ->delete();
    }

    /**
     * Return user notification for specific message.
     *
     * @param $user
     *
     * @return Notification
     */
    public function getNotification($user)
    {
        if (Chat::laravelNotifications()) {
            return $user->notifications->filter(function ($item) use ($user) {
                return $item->type == 'Musonza\Chat\Notifications\MessageSent' &&
                    $item->data['message_id'] == $this->id &&
                    $item->data['conversation_id'] == $this->conversation_id &&
                    $item->notifiable_id == $user->id;
            })->first();
        }

        return MessageNotification::where('user_id', $user->id)
            ->where('message_id', $this->id)
            ->select(['mc_message_notification.*', 'mc_message_notification.updated_at as read_at'])
            ->first();
    }

    /**
     * Marks message as read.
     *
     * @param User $user
     *
     * @return void
     */
    public function markRead($user)
    {
        $this->getNotification($user)->markAsRead();
    }
}
