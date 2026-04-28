<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    protected $table = 'balance';

    public $timestamps = false;

    protected $fillable = [
        'balance',
        'last_transaction_id',
        'updated_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function lastTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'last_transaction_id');
    }

    // ==================== HELPERS ====================

    /**
     * Get current balance (singleton pattern - only 1 row)
     */
    public static function current(): self
    {
        return self::first() ?? self::create(['balance' => 0]);
    }

    /**
     * Update balance after transaction
     */
    public static function updateAfterTransaction(Transaction $transaction): self
    {
        $balance = self::current();

        $balance->update([
            'balance' => $transaction->balance_after,
            'last_transaction_id' => $transaction->id,
            'updated_at' => now(),
        ]);

        return $balance;
    }
}
