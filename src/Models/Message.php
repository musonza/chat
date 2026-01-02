<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
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
        'data',
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
        'flagged'      => 'boolean',
        'data'         => 'array',
        'is_encrypted' => 'boolean',
    ];

    protected $appends = ['sender'];

    public function participation()
    {
        return $this->belongsTo(Participation::class, 'participation_id');
    }

    /**
     * Encrypt the message body if encryption is enabled.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setBodyAttribute($value)
    {
        if (Chat::shouldEncryptMessages() && $value !== null) {
            $this->attributes['body']         = Crypt::encryptString($value);
            $this->attributes['is_encrypted'] = true;
        } else {
            $this->attributes['body']         = $value;
            $this->attributes['is_encrypted'] = false;
        }
    }

    /**
     * Decrypt the message body if it was encrypted.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getBodyAttribute($value)
    {
        if ($this->is_encrypted && $value !== null) {
            return Crypt::decryptString($value);
        }

        return $value;
    }

    public function getSenderAttribute()
    {
        $participantModel = $this->participation->messageable;

        if (! isset($participantModel)) {
            return null;
        }

        // Check if model has customized participant details via accessor or explicit override
        if ($this->hasCustomParticipantDetails($participantModel)) {
            return $participantModel->getParticipantDetails();
        }

        $fields = Chat::senderFieldsWhitelist();

        return $fields ? $this->participation->messageable->only($fields) : $this->participation->messageable;
    }

    /**
     * Check if the model has customized getParticipantDetails.
     *
     * Returns true if the model defines getParticipantDetailsAttribute accessor
     * or explicitly overrides getParticipantDetails method (not from trait).
     */
    private function hasCustomParticipantDetails(Model $model): bool
    {
        // Check for the accessor (documented customization approach)
        if (method_exists($model, 'getParticipantDetailsAttribute')) {
            return true;
        }

        // Check if getParticipantDetails is explicitly defined in the model class
        // by comparing the source file with the trait file
        if (method_exists($model, 'getParticipantDetails')) {
            $reflection = new \ReflectionMethod($model, 'getParticipantDetails');
            $sourceFile = $reflection->getFileName();

            // If method comes from a different file than the Messageable trait,
            // it means the user has overridden it
            $traitFile = (new \ReflectionClass(\Musonza\Chat\Traits\Messageable::class))->getFileName();

            return $sourceFile !== $traitFile;
        }

        return false;
    }

    public function unreadCount(Model $participant)
    {
        return MessageNotification::where('messageable_id', $participant->getKey())
            ->where('is_seen', 0)
            ->where('messageable_type', $participant->getMorphClass())
            ->count();
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Adds a message to a conversation.
     */
    public function send(Conversation $conversation, string $body, Participation $participant, string $type = 'text', array $data = []): Model
    {
        $message = $conversation->messages()->create([
            'body'             => $body,
            'participation_id' => $participant->getKey(),
            'type'             => $type,
            'data'             => $data,
        ]);

        if (Chat::broadcasts()) {
            broadcast(new MessageWasSent($message))->toOthers();
        }

        $this->createNotifications($message);

        return $message;
    }

    /**
     * Creates an entry in the message_notification table for each participant
     * This will be used to determine if a message is read or deleted.
     *
     * @param  Message  $message
     */
    protected function createNotifications($message)
    {
        MessageNotification::make($message, $message->conversation);
    }

    /**
     * Deletes a message for the participant.
     */
    public function trash(Model $participant): void
    {
        MessageNotification::where('messageable_id', $participant->getKey())
            ->where('messageable_type', $participant->getMorphClass())
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
     */
    public function getNotification(Model $participant): MessageNotification
    {
        return MessageNotification::where('messageable_id', $participant->getKey())
            ->where('messageable_type', $participant->getMorphClass())
            ->where('message_id', $this->id)
            ->select([
                '*',
                'updated_at as read_at',
            ])
            ->first();
    }

    /**
     * Marks message as read.
     */
    public function markRead($participant): void
    {
        $this->getNotification($participant)->markAsRead();
    }

    public function flagged(Model $participant): bool
    {
        return (bool) MessageNotification::where('messageable_id', $participant->getKey())
            ->where('message_id', $this->id)
            ->where('messageable_type', $participant->getMorphClass())
            ->where('flagged', 1)
            ->first();
    }

    public function toggleFlag(Model $participant): self
    {
        MessageNotification::where('messageable_id', $participant->getKey())
            ->where('message_id', $this->id)
            ->where('messageable_type', $participant->getMorphClass())
            ->update(['flagged' => $this->flagged($participant) ? false : true]);

        return $this;
    }
}
