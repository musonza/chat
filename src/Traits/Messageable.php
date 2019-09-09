<?php

namespace Musonza\Chat\Traits;

use App\Models\Like;
use Musonza\Chat\Models\ConversationUser;

trait Messageable
{
    public function messageable()
    {
        return $this->morphTo();
    }

    public function conversations()
    {
        return $this->morphMany(ConversationUser::class, 'messageable');
    }

    public function joinConversation($conversationId)
    {
        $participation = new ConversationUser([
            'messageable_id' => $this->getKey(),
            'messageable_type' => get_class($this),
            'conversation_id' => $conversationId
        ]);

        $this->conversations()->save($participation);
    }

    public function leaveConversation($conversationId)
    {
        $this->conversations()->where([
            'messageable_id' => $this->getKey(),
            'messageable_type' => get_class($this),
            'conversation_id' => $conversationId
        ])->delete();
    }
}