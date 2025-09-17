<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeCatalogCache extends Model
{
    protected $table = 'anime_catalog_cache';

    protected $fillable = [
        'category',
        'page',
        'anime_ids',
        'cached_date',
        'has_next_page',
    ];

    protected $casts = [
        'page' => 'int',
        'anime_ids' => 'array',
        'cached_date' => 'immutable_date',
        'has_next_page' => 'boolean',
    ];
}
