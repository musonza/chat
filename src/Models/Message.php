<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\BaseModel;
use Musonza\Chat\Chat;
use Musonza\Chat\Eventing\EventGenerator;

class Message extends BaseModel
{
    use EventGenerator;

    protected $fillable = [
        'body',
        'participation_id',
        'type'
    ];

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
        return $this->belongsTo(ConversationUser::class, 'user_id');
    }

    public function unreadCount($user)
    {
        return MessageNotification::where('messageable_id', $user->getKey())
            ->where('is_seen', 0)
            ->where('messageable_type', get_class($user))
            ->count();
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Adds a message to a conversation.
     * @param Conversation $conversation
     * @param string $body
     * @param ConversationUser $participant
     * @param string $type
     * @return Model
     */
    public function send(Conversation $conversation, string $body, ConversationUser $participant, string $type = 'text'): Model
    {
        $message = $conversation->messages()->create([
            'body'    => $body,
            'participation_id' => $participant->getKey(),
            'type'    => $type,
        ]);

        $messageWasSent = Chat::sentMessageEvent();
        $message->load('sender');
        $this->raise(new $messageWasSent($message));

        return $message;
    }

    /**
     * Deletes a message.
     */
    public function trash($user)
    {
        return MessageNotification::where('messageable_id', $user->getKey())
            ->where('messageable_type', get_class($user))
            ->where('message_id', $this->getKey())
            ->delete();
    }

    /**
     * Return user notification for specific message.
     */
    public function getNotification($user): MessageNotification
    {
        return MessageNotification::where('messageable_id', $user->getKey())
            ->where('messageable_type', get_class($user))
            ->where('message_id', $this->id)
            ->select(['mc_message_notification.*', 'mc_message_notification.updated_at as read_at'])
            ->first();
    }

    /**
     * Marks message as read.
     */
    public function markRead($user): void
    {
        $this->getNotification($user)->markAsRead();
    }

    public function flagged($user): bool
    {
        return (bool) MessageNotification::where('messageable_id', $user->getKey())
            ->where('message_id', $this->id)
            ->where('messageable_type', get_class($user))
            ->where('flagged', 1)
            ->first();
    }

    public function toggleFlag($user): Message
    {
        MessageNotification::where('messageable_id', $user->getKey())
            ->where('message_id', $this->id)
            ->where('messageable_type', get_class($user))
            ->update(['flagged' => $this->flagged($user) ? false : true]);

        return $this;
    }
}
