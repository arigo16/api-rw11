<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Model;

class AmountCash extends BaseExternalModel
{
    protected $table = 'amount_cash';

    protected $fillable = [
        'amount',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'double',
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
}
