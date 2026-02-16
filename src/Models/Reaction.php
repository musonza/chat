<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\BaseModel;
use Musonza\Chat\ConfigurationManager;

class Reaction extends BaseModel
{
    protected $fillable = [
        'message_id',
        'messageable_id',
        'messageable_type',
        'reaction',
    ];

    protected $table = ConfigurationManager::REACTIONS_TABLE;

    /**
     * Get the message that was reacted to.
     */
    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    /**
     * Get the participant who reacted.
     */
    public function messageable()
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by reaction type.
     */
    public function scopeOfType($query, string $reaction)
    {
        return $query->where('reaction', $reaction);
    }

    /**
     * Scope to filter by participant.
     */
    public function scopeByParticipant($query, Model $participant)
    {
        return $query->where('messageable_id', $participant->getKey())
            ->where('messageable_type', $participant->getMorphClass());
    }
}
