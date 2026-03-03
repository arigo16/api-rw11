<?php

namespace App\Models\External;

class Complaint extends BaseExternalModel
{
    protected $table = 'complaint';

    protected $fillable = [
        'house_id',
        'complaint',
        'image',
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
