<?php

namespace Musonza\Chat\Models;

use Musonza\Chat\BaseModel;

class ConversationUser extends BaseModel
{
    protected $table = 'mc_conversation_user';

    protected $fillable = [
        'conversation_id'
    ];

    /**
     * Conversation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
