<?php

namespace App\Http\Middleware;

use App\Support\Auth;
use App\Support\Session;
use Closure;
use Illuminate\Http\Request;

class StartSession
{
    public function handle(Request $request, Closure $next)
    {
        Session::start();

        $currentUser = Auth::user();
        $favoriteIds = [];

        if ($currentUser) {
            $favoriteIds = $currentUser->favorites()
                ->pluck('anime_id')
                ->map(static fn ($value) => (int) $value)
                ->all();
        }

        app('view')->share('currentUser', $currentUser);
        app('view')->share('loginError', Session::getFlash('login_error'));
        app('view')->share('openLoginModal', Session::getFlash('open_login_modal'));
        app('view')->share('loginEmail', Session::getFlash('login_email'));
        app('view')->share('favoriteIds', $favoriteIds);

        return $next($request);
    }
}
