<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeReleaseCache extends Model
{
    protected $table = 'anime_release_cache';

    protected $primaryKey = 'anime_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'anime_id',
        'episodes',
        'related',
    ];

    protected $casts = [
        'episodes' => 'array',
        'related' => 'array',
    ];
}
