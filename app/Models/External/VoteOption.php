<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Model;

class VoteOption extends BaseExternalModel
{
    protected $table = 'vote_options';

    protected $fillable = [
        'vote_id',
        'option_name',
        'option_desc',
    ];

    protected $casts = [
        'vote_id' => 'integer',
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

    public function vote()
    {
        return $this->belongsTo(Vote::class, 'vote_id');
    }

    public function answers()
    {
        return $this->hasMany(VoteAnswer::class, 'vote_option_id');
    }

    public function getVoteCountAttribute(): int
    {
        return $this->answers()->count();
    }
}
