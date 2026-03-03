<?php

namespace App\Models\External;

class Role extends BaseExternalModel
{
    protected $table = 'roles';

    protected $fillable = [
        'role_name',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(ExternalUser::class, 'role_id');
    }
}
