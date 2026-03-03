<?php

namespace App\Models\External;

class VoteAnswer extends BaseExternalModel
{
    protected $table = 'vote_answers';

    protected $fillable = [
        'house_id',
        'vote_id',
        'vote_option_id',
        'deleted_by',
    ];

    protected $casts = [
        'house_id' => 'integer',
        'vote_id' => 'integer',
        'vote_option_id' => 'integer',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function vote()
    {
        return $this->belongsTo(Vote::class, 'vote_id');
    }

    public function option()
    {
        return $this->belongsTo(VoteOption::class, 'vote_option_id');
    }
}
