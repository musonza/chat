<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\BaseModel;
use Musonza\Chat\Chat;
use Musonza\Chat\ConfigurationManager;
use Musonza\Chat\Eventing\AllParticipantsDeletedMessage;
use Musonza\Chat\Eventing\EventGenerator;
use Musonza\Chat\Eventing\MessageWasSent;

class Message extends BaseModel
{
    use EventGenerator;

    protected $fillable = [
        'body',
        'participation_id',
        'type',
    ];

    protected $table = ConfigurationManager::MESSAGES_TABLE;
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

    protected $appends = ['sender'];

    public function participation()
    {
        return $this->belongsTo(Participation::class, 'participation_id');
    }

    public function getSenderAttribute()
    {
        $participantModel = $this->participation->messageable;

        if (method_exists($participantModel, 'getParticipantDetails')) {
            return $participantModel->getParticipantDetails();
        }

        $fields = Chat::senderFieldsWhitelist();

        return $fields ? $this->participation->messageable->only($fields) : $this->participation->messageable;
    }

    public function unreadCount(Model $participant)
    {
        return MessageNotification::where('messageable_id', $participant->getKey())
            ->where('is_seen', 0)
            ->where('messageable_type', get_class($participant))
            ->count();
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Adds a message to a conversation.
     *
     * @param Conversation  $conversation
     * @param string        $body
     * @param Participation $participant
     * @param string        $type
     *
     * @return Model
     */
    public function send(Conversation $conversation, string $body, Participation $participant, string $type = 'text'): Model
    {
        $message = $conversation->messages()->create([
            'body'             => $body,
            'participation_id' => $participant->getKey(),
            'type'             => $type,
        ]);

        if (Chat::broadcasts()) {
            broadcast(new MessageWasSent($message))->toOthers();
        } else {
            event(new MessageWasSent($message));
        }

        $this->createNotifications($message);

        return $message;
    }

    /**
     * Creates an entry in the message_notification table for each participant
     * This will be used to determine if a message is read or deleted.
     *
     * @param Message $message
     */
    protected function createNotifications($message)
    {
        MessageNotification::make($message, $message->conversation);
    }

    /**
     * Deletes a message for the participant.
     *
     * @param Model $participant
     *
     * @return void
     */
    public function trash(Model $participant): void
    {
        MessageNotification::where('messageable_id', $participant->getKey())
            ->where('messageable_type', get_class($participant))
            ->where('message_id', $this->getKey())
            ->delete();

        if ($this->unDeletedCount() === 0) {
            event(new AllParticipantsDeletedMessage($this));
        }
    }

    public function unDeletedCount()
    {
        return MessageNotification::where('message_id', $this->getKey())
            ->count();
    }

    /**
     * Return user notification for specific message.
     *
     * @param Model $participant
     *
     * @return MessageNotification
     */
    public function getNotification(Model $participant): MessageNotification
    {
        return MessageNotification::where('messageable_id', $participant->getKey())
            ->where('messageable_type', get_class($participant))
            ->where('message_id', $this->id)
            ->select([
                '*',
                'updated_at as read_at',
            ])
            ->first();
    }

    /**
     * Marks message as read.
     *
     * @param $participant
     */
    public function markRead($participant): void
    {
        $this->getNotification($participant)->markAsRead();
    }

    public function flagged(Model $participant): bool
    {
        return (bool) MessageNotification::where('messageable_id', $participant->getKey())
            ->where('message_id', $this->id)
            ->where('messageable_type', get_class($participant))
            ->where('flagged', 1)
            ->first();
    }

    public function toggleFlag(Model $participant): self
    {
        MessageNotification::where('messageable_id', $participant->getKey())
            ->where('message_id', $this->id)
            ->where('messageable_type', get_class($participant))
            ->update(['flagged' => $this->flagged($participant) ? false : true]);

        return $this;
    }
}
