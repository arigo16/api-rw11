<?php

namespace App\Models\External;

class BillIplTakeover extends BaseExternalModel
{
    protected $table = 'bill_ipl_takeover';

    protected $fillable = [
        'house_id',
        'amount',
        'start_month',
        'start_year',
        'remaining_amount',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'house_id' => 'integer',
        'amount' => 'integer',
        'start_month' => 'integer',
        'start_year' => 'integer',
        'remaining_amount' => 'integer',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function repayments()
    {
        return $this->hasMany(BillIplTakeoverRepayment::class, 'bill_ipl_takeover_id');
    }
}
