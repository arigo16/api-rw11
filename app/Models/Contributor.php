<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contributor extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'amount',
        'is_active',
        'start_month',
        'start_year',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'start_month' => 'integer',
        'start_year' => 'integer',
    ];

    public const TYPES = ['RT', 'RUKO', 'LAINNYA'];

    // ==================== RELATIONSHIPS ====================

    public function bills(): HasMany
    {
        return $this->hasMany(ContributorBill::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==================== ACCESSORS ====================

    public function getStartPeriodAttribute(): ?string
    {
        if ($this->start_month && $this->start_year) {
            $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
            return $months[$this->start_month] . ' ' . $this->start_year;
        }
        return null;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}
