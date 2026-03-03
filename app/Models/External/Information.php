<?php

namespace App\Models\External;

class Information extends BaseExternalModel
{
    protected $table = 'information';

    protected $fillable = [
        'banner',
        'title',
        'content',
        'is_pin',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_pin' => 'boolean',
    ];

    // ==================== SCOPES ====================

    public function scopePinned($query)
    {
        return $query->where('is_pin', true);
    }

    public function scopeNotPinned($query)
    {
        return $query->where('is_pin', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_pin')->orderByDesc('created_at');
    }
}
