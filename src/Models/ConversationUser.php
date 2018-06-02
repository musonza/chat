<?php

namespace Musonza\Chat\Models;

use Eloquent;

class ConversationUser extends Eloquent
{
    protected $table = 'mc_conversation_user';

    /**
     * Conversation.
     *
     * @return Relationship
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
