<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Musonza\Chat\BaseModel;
use Musonza\Chat\Chat;
use Musonza\Chat\ConfigurationManager;
use Musonza\Chat\Eventing\AllParticipantsDeletedMessage;
use Musonza\Chat\Eventing\EventGenerator;
use Musonza\Chat\Eventing\MessageReactionAdded;
use Musonza\Chat\Eventing\MessageReactionRemoved;
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
     * Get all reactions for this message.
     */
    public function reactions()
    {
        return $this->hasMany(Reaction::class, 'message_id');
    }

    /**
     * Add a reaction to this message.
     */
    public function react(Model $participant, string $reaction): Reaction
    {
        $reactionModel = Reaction::updateOrCreate(
            [
                'message_id'       => $this->getKey(),
                'messageable_id'   => $participant->getKey(),
                'messageable_type' => $participant->getMorphClass(),
                'reaction'         => $reaction,
            ]
        );

        if (Chat::broadcasts()) {
            broadcast(new MessageReactionAdded($reactionModel))->toOthers();
        }

        return $reactionModel;
    }

    /**
     * Remove a reaction from this message.
     */
    public function unreact(Model $participant, string $reaction): bool
    {
        $deleted = Reaction::where('message_id', $this->getKey())
            ->where('messageable_id', $participant->getKey())
            ->where('messageable_type', $participant->getMorphClass())
            ->where('reaction', $reaction)
            ->delete();

        if ($deleted && Chat::broadcasts()) {
            broadcast(new MessageReactionRemoved(
                $this->getKey(),
                $this->conversation_id,
                $reaction,
                $participant->getKey(),
                $participant->getMorphClass()
            ))->toOthers();
        }

        return $deleted > 0;
    }

    /**
     * Toggle a reaction on this message.
     * Adds the reaction if not present, removes it if already present.
     */
    public function toggleReaction(Model $participant, string $reaction): array
    {
        $existing = Reaction::where('message_id', $this->getKey())
            ->where('messageable_id', $participant->getKey())
            ->where('messageable_type', $participant->getMorphClass())
            ->where('reaction', $reaction)
            ->first();

        if ($existing) {
            $this->unreact($participant, $reaction);

            return ['added' => false, 'reaction' => null];
        }

        return ['added' => true, 'reaction' => $this->react($participant, $reaction)];
    }

    /**
     * Get reactions grouped by reaction type with counts.
     */
    public function getReactionsSummary()
    {
        return $this->reactions()
            ->select('reaction')
            ->selectRaw('count(*) as count')
            ->groupBy('reaction')
            ->get()
            ->pluck('count', 'reaction');
    }

    /**
     * Check if a participant has reacted with a specific reaction.
     */
    public function hasReacted(Model $participant, ?string $reaction = null): bool
    {
        $query = Reaction::where('message_id', $this->getKey())
            ->where('messageable_id', $participant->getKey())
            ->where('messageable_type', $participant->getMorphClass());

        if ($reaction !== null) {
            $query->where('reaction', $reaction);
        }

        return $query->exists();
    }

    /**
     * Get all reactions by a specific participant on this message.
     */
    public function getReactionsByParticipant(Model $participant)
    {
        return $this->reactions()
            ->where('messageable_id', $participant->getKey())
            ->where('messageable_type', $participant->getMorphClass())
            ->get();
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
