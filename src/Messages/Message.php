<?php

namespace Musonza\Chat\Messages;

use Eloquent;
use Musonza\Chat\Chat;
use Musonza\Chat\Conversations\Conversation;
use Musonza\Chat\Eventing\EventGenerator;
use Musonza\Chat\Notifications\MessageNotification;

class Message extends Eloquent
{
    protected $fillable = ['body', 'user_id', 'type'];

    protected $table = 'messages';

    protected $touches = ['conversation'];

    use EventGenerator;

    public function sender()
    {
        return $this->belongsTo(Chat::userModel(), 'user_id');
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

        return $this;
    }

    /**
     * Deletes a message.
     *
     * @param int $messageId
     * @param int $userId
     *
     * @return
     */
    public function trash($messageId, $userId)
    {
        return MessageNotification::where('user_id', $userId)
            ->where('message_id', $messageId)
            ->delete();
    }

    /**
     * marks message as read.
     *
     * @param int $messageId
     * @param int $userId
     *
     * @return
     */
    public function messageRead($messageId, $userId)
    {
        return MessageNotification::where('user_id', $userId)
            ->where('message_id', $messageId)
            ->update(['is_seen' => 1]);
    }
}
