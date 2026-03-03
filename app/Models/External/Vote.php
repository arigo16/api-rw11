<?php

namespace App\Models\External;

class Vote extends BaseExternalModel
{
    protected $table = 'votes';

    protected $fillable = [
        'multi',
        'public',
        'is_lock',
        'title',
        'description',
        'start_vote',
        'end_vote',
        'deactivated',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'multi' => 'boolean',
        'public' => 'boolean',
        'is_lock' => 'integer',
        'deactivated' => 'boolean',
        'start_vote' => 'datetime',
        'end_vote' => 'datetime',
    ];

    public function options()
    {
        return $this->hasMany(VoteOption::class, 'vote_id');
    }

    public function answers()
    {
        return $this->hasMany(VoteAnswer::class, 'vote_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('deactivated', '0')
            ->where('start_vote', '<=', now())
            ->where('end_vote', '>=', now());
    }

    public function scopePublic($query)
    {
        return $query->where('public', '1');
    }

    // ==================== ACCESSORS ====================

    public function getIsActiveAttribute(): bool
    {
        return !$this->deactivated
            && $this->start_vote <= now()
            && $this->end_vote >= now();
    }

    public function getIsMultiChoiceAttribute(): bool
    {
        return (bool) $this->multi;
    }
}
