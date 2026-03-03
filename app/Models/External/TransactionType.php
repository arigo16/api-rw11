<?php

namespace App\Models\External;

class TransactionType extends BaseExternalModel
{
    protected $table = 'transaction_types';

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
