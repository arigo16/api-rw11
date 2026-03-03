<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Model;

class AmountIpl extends BaseExternalModel
{
    protected $table = 'amount_ipl';

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
