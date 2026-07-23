<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionType extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'type_id');
    }

    public function contributors(): HasMany
    {
        return $this->hasMany(Contributor::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
