<?php

namespace App\Models\External;

class Banner extends BaseExternalModel
{
    protected $table = 'banner';

    protected $fillable = [
        'banner',
        'deleted_by',
        'created_by',
        'updated_by',
    ];
}
