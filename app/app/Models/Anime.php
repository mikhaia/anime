<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Anime extends Model
{
    protected $table = 'anime';

    protected $fillable = [
        'id',
        'title',
        'title_english',
        'poster',
        'poster_url',
        'type',
        'year',
        'episodes_total',
        'alias',
    ];

    public $incrementing = false;

    protected $keyType = 'int';

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function watchProgress(): HasMany
    {
        return $this->hasMany(WatchProgress::class);
    }

    public function releaseCache(): HasOne
    {
        return $this->hasOne(AnimeReleaseCache::class);
    }

    public function getPosterUrlAttribute($value): ?string
    {
        if (is_string($this->poster) && trim($this->poster) !== '') {
            return '/' . ltrim($this->poster, '/');
        }

        return $value !== null ? (string) $value : null;
    }
}
