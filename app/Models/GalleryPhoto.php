<?php

namespace App\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GalleryPhoto extends Model
{
    use HasAudit, SoftDeletes;

    protected $fillable = [
        'gallery_event_id',
        'foto',
        'caption',
        'sequence',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(GalleryEvent::class, 'gallery_event_id');
    }
}
