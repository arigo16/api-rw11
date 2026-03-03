<?php

namespace App\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dokumen extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'dokumen';

    protected $fillable = [
        'nama',
        'kategori',
        'deskripsi',
        'file_path',
        'file_type',
        'file_size',
        'is_public',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
