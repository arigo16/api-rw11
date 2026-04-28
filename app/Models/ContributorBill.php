<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributorBill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contributor_id',
        'year_bill',
        'month_bill',
        'amount',
        'status',
        'transaction_id',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'year_bill' => 'integer',
        'month_bill' => 'integer',
        'paid_at' => 'datetime',
    ];

    public const STATUS_UNPAID = 'UNPAID';
    public const STATUS_PAID = 'PAID';

    // ==================== RELATIONSHIPS ====================

    public function contributor(): BelongsTo
    {
        return $this->belongsTo(Contributor::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // ==================== SCOPES ====================

    public function scopeUnpaid($query)
    {
        return $query->where('status', self::STATUS_UNPAID);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year_bill', $year)->where('month_bill', $month);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year_bill', $year);
    }

    // ==================== ACCESSORS ====================

    public function getPeriodLabelAttribute(): string
    {
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $months[$this->month_bill] . ' ' . $this->year_bill;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
