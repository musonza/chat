<?php

namespace Musonza\Chat\Models;

use Musonza\Chat\BaseModel;
use Musonza\Chat\Chat;
use Musonza\Chat\Eventing\EventGenerator;
use Musonza\Chat\Eventing\MessageWasSent;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\MessageNotification;

class Message extends BaseModel
{
    use EventGenerator;

    protected $fillable = ['body', 'user_id', 'type'];
    protected $table = 'mc_messages';
    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['conversation'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'flagged' => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(Chat::userModel(), 'user_id');
    }

    public function unreadCount($user)
    {
        return MessageNotification::where('user_id', $user->id)
            ->where('is_seen', 0)
            ->count();
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
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
            'body' => $body,
            'user_id' => $userId,
            'type' => $type,
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

    public function flagged($user)
    {
        return !!MessageNotification::where('user_id', $user->id)
            ->where('message_id', $this->id)
            ->where('flagged', 1)
            ->first();
    }

    public function toggleFlag($user)
    {
        MessageNotification::where('user_id', $user->id)
            ->where('message_id', $this->id)
            ->update(['flagged' => $this->flagged($user) ? false : true]);

        return $this;
    }
}
