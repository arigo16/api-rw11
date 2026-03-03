<?php

namespace App\Models\External;

class Contribution extends BaseExternalModel
{
    protected $table = 'contribution';

    protected $fillable = [
        'banner',
        'name',
        'description',
        'goals',
        'min_amount',
        'start_date',
        'end_date',
        'is_closed',
        'house_id',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'goals' => 'integer',
        'min_amount' => 'integer',
        'is_closed' => 'boolean',
        'house_id' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function transactions()
    {
        return $this->hasMany(ContributionTransaction::class, 'contribution_id');
    }

    // ==================== SCOPES ====================

    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_closed', false)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    // ==================== ACCESSORS ====================

    public function getTotalCollectedAttribute(): float
    {
        return $this->transactions()
            ->where('mutation', 'DEBIT')
            ->sum('amount');
    }

    public function getProgressPercentageAttribute(): float
    {
        if (!$this->goals || $this->goals == 0) {
            return 0;
        }

        return min(100, ($this->total_collected / $this->goals) * 100);
    }
}
