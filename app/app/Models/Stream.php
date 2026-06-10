<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stream extends Model
{
    protected $fillable = [
        'anime_id',
        'episode_id',
        'quality',
        'url',
        'cached_at',
    ];

    protected $casts = [
        'cached_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Stream $stream) {
            if ($stream->isDirty('url')) {
                $stream->cached_at = now();
            }
        });
    }

    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
