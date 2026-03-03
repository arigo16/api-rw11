<?php

namespace App\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengurus extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'pengurus';

    protected $fillable = [
        'nama',
        'jabatan',
        'bidang',
        'periode',
        'sequence',
        'foto',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
