<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Torrent extends Model
{
    protected $table = 'torrents';

    protected $fillable = [
        'anime_id',
        'label',
        'quality',
        'size',
        'magnet',
    ];

    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }
}
