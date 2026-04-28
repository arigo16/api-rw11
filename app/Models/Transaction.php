<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id_settlement',
        'mutation',
        'type_id',
        'amount',
        'balance_before',
        'balance_after',
        'transaction_date',
        'description',
        'attachment',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function type(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== SCOPES ====================

    public function scopeIn($query)
    {
        return $query->where('mutation', 'IN');
    }

    public function scopeOut($query)
    {
        return $query->where('mutation', 'OUT');
    }

    // ==================== ACCESSORS ====================

    public function getIsInAttribute(): bool
    {
        return $this->mutation === 'IN';
    }

    public function getIsOutAttribute(): bool
    {
        return $this->mutation === 'OUT';
    }

    // ==================== HELPERS ====================

    /**
     * Generate unique settlement ID
     * Format: TRX-YYYYMM-XXXX
     */
    public static function generateSettlementId(): string
    {
        $prefix = 'TRX-' . date('Ym') . '-';

        $lastTransaction = self::where('id_settlement', 'like', $prefix . '%')
            ->orderBy('id_settlement', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->id_settlement, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
