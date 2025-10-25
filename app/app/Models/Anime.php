<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'is_ongoing',
        'age_rating',
    ];

    public $incrementing = false;

    protected $keyType = 'int';

    protected $casts = [
        'is_ongoing' => 'boolean',
    ];

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function watchProgress(): HasMany
    {
        return $this->hasMany(WatchProgress::class);
    }

    public function streams()
    {
        return $this->hasMany(Stream::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('number');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'anime_genre');
    }

    public function relates(): HasMany
    {
        return $this->hasMany(Relate::class);
    }

    public function getPosterUrlAttribute($value): ?string
    {
        if (is_string($this->poster) && trim($this->poster) !== '') {
            return '/' . ltrim($this->poster, '/');
        }

        return $value !== null ? (string) $value : null;
    }
}
