<?php

namespace App\Models\External;

class Suggestion extends BaseExternalModel
{
    protected $table = 'suggestion';

    protected $fillable = [
        'house_id',
        'suggestion',
        'is_read',
        'deleted_by',
    ];

    protected $casts = [
        'house_id' => 'integer',
        'is_read' => 'boolean',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    // ==================== SCOPES ====================

    public function scopeUnread($query)
    {
        return $query->where('is_read', '0');
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', '1');
    }
}
