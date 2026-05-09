<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Musonza\Chat\BaseModel;
use Musonza\Chat\ConfigurationManager;

class Participation extends BaseModel
{
    //    use SoftDeletes;

    protected $table = ConfigurationManager::PARTICIPATION_TABLE;

    protected $fillable = [
        'conversation_id',
        'settings',
        'archived_at',
    ];

    protected $casts = [
        'settings'    => 'array',
        'archived_at' => 'datetime',
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

    public function messageable()
    {
        return $this->morphTo()->with('participation');
    }

    public function archive(): self
    {
        if ($this->archived_at === null) {
            $this->archived_at = $this->freshTimestamp();
            $this->save();
        }

        return $this;
    }

    public function unarchive(): self
    {
        if ($this->archived_at !== null) {
            $this->archived_at = null;
            $this->save();
        }

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull($this->getTable() . '.archived_at');
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull($this->getTable() . '.archived_at');
    }
}
