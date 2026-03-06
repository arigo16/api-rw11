<?php

namespace App\Models\External;

class HouseOwner extends BaseExternalModel
{
    protected $table = 'house_owner';

    protected $fillable = [
        'house_id',
        'kk',
        'name',
        'handphone',
        'sum_family',
        'religion',
    ];

    protected $casts = [
        'house_id' => 'integer',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    // ==================== CONSTANTS ====================

    public const SUM_FAMILY = [
        'TK',
        'K0',
        'K1',
        'K2',
        'K3',
        'K4',
        'K5',
        'TK0',
        'TK1',
        'TK2',
        'TK3',
        'TK4',
        'TK5',
    ];
}
