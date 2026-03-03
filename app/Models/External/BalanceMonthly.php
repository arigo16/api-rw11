<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Model;

class BalanceMonthly extends BaseExternalModel
{
    protected $table = 'balance_monthly';

    protected $fillable = [
        'year',
        'month',
        'in_main',
        'in_pkk',
        'out_main',
        'out_pkk',
        'balance_main',
        'balance_pkk',
        'last_update_id_transactions',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'in_main' => 'double',
        'in_pkk' => 'double',
        'out_main' => 'double',
        'out_pkk' => 'double',
        'balance_main' => 'double',
        'balance_pkk' => 'double',
        'last_update_id_transactions' => 'integer',
    ];

    // Disable soft deletes
    use \Illuminate\Database\Eloquent\SoftDeletes {
        \Illuminate\Database\Eloquent\SoftDeletes::bootSoftDeletes as parentBootSoftDeletes;
    }

    public static function bootSoftDeletes() {}

    public function getDeletedAtColumn()
    {
        return null;
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
