<?php

namespace App\Models\External;

class ContributionTransaction extends BaseExternalModel
{
    protected $table = 'contribution_transaction';

    protected $fillable = [
        'contribution_id',
        'id_settlement',
        'notes',
        'amount',
        'mutation',
        'image',
        'house_id',
        'contributor',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'contribution_id' => 'integer',
        'amount' => 'double',
        'house_id' => 'integer',
    ];

    public function contribution()
    {
        return $this->belongsTo(Contribution::class, 'contribution_id');
    }

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    // ==================== SCOPES ====================

    public function scopeDebit($query)
    {
        return $query->where('mutation', 'DEBIT');
    }

    public function scopeKredit($query)
    {
        return $query->where('mutation', 'KREDIT');
    }
}
