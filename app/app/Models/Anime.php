<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anime extends Model
{
    protected $table = 'anime';

    protected $fillable = [
        'id',
        'title',
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
}
