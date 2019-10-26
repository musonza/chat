<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Musonza\Chat\BaseModel;

class ConversationUser extends BaseModel
{
    protected $table = 'mc_conversation_user';

    protected $fillable = [
        'conversation_id',
    ];

    /**
     * Conversation.
     *
     * @return BelongsTo
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
