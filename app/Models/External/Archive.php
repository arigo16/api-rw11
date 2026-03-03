<?php

namespace App\Models\External;

class Archive extends BaseExternalModel
{
    protected $table = 'archieve'; // Note: typo in original table name

    protected $fillable = [
        'title',
        'description',
        'document',
        'deleted_by',
        'created_by',
        'updated_by',
    ];
}
