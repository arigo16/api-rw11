<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Model;

class AmountPkk extends BaseExternalModel
{
    protected $table = 'amount_pkk';

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
