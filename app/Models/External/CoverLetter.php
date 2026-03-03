<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Model;

class CoverLetter extends BaseExternalModel
{
    protected $table = 'cover_letter';

    protected $fillable = [
        'type',
        'no',
        'house_id',
        'name',
        'gender',
        'pob',
        'dob',
        'religion',
        'marital',
        'job',
        'report_sign',
        'letter_requirements',
        'description',
        'image_letter',
        'status',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'no' => 'integer',
        'house_id' => 'integer',
        'dob' => 'date',
    ];

    // Disable soft deletes
    use \Illuminate\Database\Eloquent\SoftDeletes {
        \Illuminate\Database\Eloquent\SoftDeletes::bootSoftDeletes as parentBootSoftDeletes;
    }

    public static function bootSoftDeletes() {}

    public function getDeletedAtColumn()
    {
        return null;
    }

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    // ==================== SCOPES ====================

    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'CLOSED');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'REJECTED');
    }

    public function scopeSuratPengantar($query)
    {
        return $query->where('type', 'SURAT PENGANTAR');
    }

    public function scopeSuratKeterangan($query)
    {
        return $query->where('type', 'SURAT KETERANGAN');
    }
}
