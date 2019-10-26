<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Musonza\Chat\BaseModel;

class ConversationParticipant extends BaseModel
{
//    use SoftDeletes;

    protected $table = 'mc_conversation_participant';
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
