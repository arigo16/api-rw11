<?php

namespace App\Models\External;

class House extends BaseExternalModel
{
    protected $table = 'house';

    public $timestamps = true;

    protected $fillable = [
        'block',
        'no',
        'occupied',
        'occupied_by_owner',
        'pay_ipl',
        'ipl_amount',
        'pay_cash',
        'cash_amount',
        'pay_pkk',
        'pkk_amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'no' => 'integer',
        'occupied' => 'boolean',
        'occupied_by_owner' => 'boolean',
        'pay_ipl' => 'boolean',
        'ipl_amount' => 'double',
        'pay_cash' => 'boolean',
        'cash_amount' => 'double',
        'pay_pkk' => 'boolean',
        'pkk_amount' => 'double',
    ];

    // Disable soft deletes for this model (no deleted_at column)
    use \Illuminate\Database\Eloquent\SoftDeletes {
        \Illuminate\Database\Eloquent\SoftDeletes::bootSoftDeletes as parentBootSoftDeletes;
    }

    public static function bootSoftDeletes()
    {
        // Do nothing - house table doesn't have soft deletes
    }

    public function getDeletedAtColumn()
    {
        return null;
    }

    // ==================== RELATIONSHIPS ====================

    public function owner()
    {
        return $this->hasOne(HouseOwner::class, 'house_id');
    }

    public function occupant()
    {
        return $this->hasOne(HouseOccupied::class, 'house_id');
    }

    public function users()
    {
        return $this->hasMany(ExternalUser::class, 'house_id');
    }

    public function billsIpl()
    {
        return $this->hasMany(BillIpl::class, 'house_id');
    }

    public function billsCash()
    {
        return $this->hasMany(BillCash::class, 'house_id');
    }

    public function billsPkk()
    {
        return $this->hasMany(BillPkk::class, 'house_id');
    }

    public function billIplTakeover()
    {
        return $this->hasOne(BillIplTakeover::class, 'house_id');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'house_id');
    }

    public function suggestions()
    {
        return $this->hasMany(Suggestion::class, 'house_id');
    }

    public function coverLetters()
    {
        return $this->hasMany(CoverLetter::class, 'house_id');
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class, 'house_id');
    }

    public function voteAnswers()
    {
        return $this->hasMany(VoteAnswer::class, 'house_id');
    }

    public function members()
    {
        return $this->hasMany(HouseMember::class, 'house_id');
    }

    // ==================== ACCESSORS ====================

    public function getFullAddressAttribute(): string
    {
        return "Blok {$this->block} No. {$this->no}";
    }
}
