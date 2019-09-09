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

    public function scopeWhereConversation($query, $conversationId)
    {
        return $query->whereHas('conversation_user', function ($q) use ($conversationId) {
            $q->where('conversation_id', $conversationId);
        });
    }

    public function joinConversation($conversationId)
    {
        $participation = new ConversationUser([
            'user_id' => $this->getKey(),
            'conversation_id' => $conversationId
        ]);

        $this->conversations()->save($participation);
    }

    public function leaveConversation()
    {

    }
}