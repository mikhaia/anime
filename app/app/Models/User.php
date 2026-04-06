<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** @mixin \Illuminate\Database\Eloquent\Model */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'password',
    ];

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function watchProgress(): HasMany
    {
        return $this->hasMany(WatchProgress::class);
    }

    public function getAvatarPathAttribute($value): string
    {
        return $value ?: 'img/no-avatar.jpg';
    }
}
