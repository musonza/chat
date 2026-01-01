<?php

namespace Musonza\Chat\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Musonza\Chat\Exceptions\InvalidDirectMessageNumberOfParticipants;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Participation;

trait Messageable
{
    /**
     * Get participant details for display in messages.
     *
     * Override this method in your model to customize the returned data.
     * If a getParticipantDetailsAttribute accessor exists, it will be used instead.
     */
    public function getParticipantDetails(): array
    {
        // Check if the model has a custom accessor
        if (method_exists($this, 'getParticipantDetailsAttribute')) {
            return $this->getParticipantDetailsAttribute();
        }

        // Default implementation returns name if available
        if (isset($this->name)) {
            return ['name' => $this->name];
        }

        return [];
    }

    public function conversations()
    {
        return $this->participation->pluck('conversation');
    }

    public function participation(): MorphMany
    {
        return $this->morphMany(Participation::class, 'messageable');
    }

    public function joinConversation(Conversation $conversation)
    {
        if ($conversation->isDirectMessage() && $conversation->participants()->count() == 2) {
            throw new InvalidDirectMessageNumberOfParticipants;
        }

        $participation = new Participation([
            'messageable_id'   => $this->getKey(),
            'messageable_type' => $this->getMorphClass(),
            'conversation_id'  => $conversation->getKey(),
        ]);

        $this->participation()->save($participation);
    }

    public function leaveConversation($conversationId)
    {
        $this->participation()->where([
            'messageable_id'   => $this->getKey(),
            'messageable_type' => $this->getMorphClass(),
            'conversation_id'  => $conversationId,
        ])->delete();
    }
}
