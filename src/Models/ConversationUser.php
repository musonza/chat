<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationUser extends Model
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
