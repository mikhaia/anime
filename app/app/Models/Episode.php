<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    protected $fillable = [
        'anime_id',
        'number',
        'title',
        'duration',
    ];

    protected $casts = [
        'duration' => 'integer',
    ];

    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }
}
