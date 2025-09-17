<?php

namespace App\Support;

use App\Models\User;

class Auth
{
    public static function user(): ?User
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(User $user): void
    {
        Session::put('user_id', $user->getKey());
    }

    public static function logout(): void
    {
        Session::forget('user_id');
    }
}
