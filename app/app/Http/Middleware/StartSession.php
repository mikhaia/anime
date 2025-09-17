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

        app('view')->share('currentUser', Auth::user());
        app('view')->share('loginError', Session::getFlash('login_error'));
        app('view')->share('openLoginModal', Session::getFlash('open_login_modal'));
        app('view')->share('loginEmail', Session::getFlash('login_email'));

        return $next($request);
    }
}
