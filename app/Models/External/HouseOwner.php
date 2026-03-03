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
}
