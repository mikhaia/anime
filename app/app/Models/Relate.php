<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relate extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'anime_id',
        'title',
        'title_english',
        'alias',
    ];

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';
}
