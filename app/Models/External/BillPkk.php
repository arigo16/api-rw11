<?php

namespace App\Models\External;

class BillPkk extends BaseExternalModel
{
    protected $table = 'bill_pkk';

    protected $fillable = [
        'house_id',
        'year_bill',
        'month_bill',
        'amount',
        'transactions_id',
        'status',
        'token',
        'token_exp',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'house_id' => 'integer',
        'year_bill' => 'integer',
        'month_bill' => 'integer',
        'amount' => 'double',
        'transactions_id' => 'integer',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transactions_id');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'UNPAID');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year_bill', $year)->where('month_bill', $month);
    }
}
