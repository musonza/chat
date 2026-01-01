<?php

namespace Musonza\Chat\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Musonza\Chat\Exceptions\InvalidDirectMessageNumberOfParticipants;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Participation;

trait Messageable
{
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
            throw new InvalidDirectMessageNumberOfParticipants();
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
