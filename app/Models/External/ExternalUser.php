<?php

namespace App\Models\External;

class ExternalUser extends BaseExternalModel
{
    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'role_id',
        'house_id',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'house_id' => 'integer',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }
}
