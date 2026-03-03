<?php

namespace App\Models\External;

class BillIplTakeoverRepayment extends BaseExternalModel
{
    protected $table = 'bill_ipl_takeover_repayment';

    protected $fillable = [
        'bill_ipl_takeover_id',
        'transactions_id',
        'amount',
        'status',
        'token',
        'token_exp',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bill_ipl_takeover_id' => 'integer',
        'transactions_id' => 'integer',
        'amount' => 'double',
    ];

    public function takeover()
    {
        return $this->belongsTo(BillIplTakeover::class, 'bill_ipl_takeover_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transactions_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'EXPIRED');
    }
}
