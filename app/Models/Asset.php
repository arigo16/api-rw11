<?php

namespace App\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasAudit, SoftDeletes;

    protected $fillable = [
        'nama',
        'kategori',
        'kondisi',
        'deskripsi',
        'foto',
        'jumlah',
        'tanggal_perolehan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_perolehan' => 'date',
        ];
    }
}
