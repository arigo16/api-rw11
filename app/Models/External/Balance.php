<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Model;

class Balance extends BaseExternalModel
{
    protected $table = 'balance';

    protected $fillable = [
        'balance',
        'balance_pkk',
        'last_update_id_transactions',
        'created_by',
    ];

    protected $casts = [
        'balance' => 'double',
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

    public function getTotalBalanceAttribute(): float
    {
        return $this->balance + $this->balance_pkk;
    }
}
