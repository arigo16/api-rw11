<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Model;

class Transaction extends BaseExternalModel
{
    protected $table = 'transactions';

    protected $fillable = [
        'category',
        'type',
        'id_settlement',
        'notes',
        'amount',
        'mutation',
        'image',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'double',
    ];

    // Disable soft deletes for this model
    use \Illuminate\Database\Eloquent\SoftDeletes {
        \Illuminate\Database\Eloquent\SoftDeletes::bootSoftDeletes as parentBootSoftDeletes;
    }

    public static function bootSoftDeletes()
    {
        // transactions table doesn't have soft deletes
    }

    public function getDeletedAtColumn()
    {
        return null;
    }

    // ==================== SCOPES ====================

    public function scopeUtama($query)
    {
        return $query->where('category', 'UTAMA');
    }

    public function scopePkk($query)
    {
        return $query->where('category', 'PKK');
    }

    public function scopeDebit($query)
    {
        return $query->where('mutation', 'DEBIT');
    }

    public function scopeKredit($query)
    {
        return $query->where('mutation', 'KREDIT');
    }

    // ==================== ACCESSORS ====================

    public function getIsDebitAttribute(): bool
    {
        return $this->mutation === 'DEBIT';
    }

    public function getIsKreditAttribute(): bool
    {
        return $this->mutation === 'KREDIT';
    }
}
