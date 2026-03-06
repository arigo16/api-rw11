<?php

namespace App\Models\External;

class HouseMember extends BaseExternalModel
{
    protected $table = 'house_member';

    protected $fillable = [
        'house_id',
        'kk',
        'name',
        'handphone',
        'religion',
        'relatives',
        'is_domicile',
    ];

    protected $casts = [
        'house_id' => 'integer',
        'is_domicile' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    // ==================== CONSTANTS ====================

    public const RELIGIONS = [
        'ISLAM',
        'KRISTEN',
        'KATHOLIK',
        'BUDDHA',
        'HINDU',
        'KHONGHUCU',
    ];

    public const RELATIVES = [
        'Ibu',
        'Ayah',
        'Anak',
        'Istri',
        'Suami',
        'Kakek',
        'Nenek',
        'Cucu',
        'Paman',
        'Bibi',
        'Saudara Kandung',
        'Saudara Tiri',
        'Lainnya',
    ];
}
