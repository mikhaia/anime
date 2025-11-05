<?php

namespace App\Http\Middleware;

use App\Support\Auth;
use App\Support\Session;
use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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
                ->map(static fn($value) => (int) $value)
                ->all();
        }

        app('view')->share('currentUser', $currentUser);
        app('view')->share('loginError', Session::getFlash('login_error'));
        app('view')->share('openLoginModal', Session::getFlash('open_login_modal'));
        app('view')->share('loginEmail', Session::getFlash('login_email'));
        app('view')->share('loginRedirect', Session::getFlash('login_redirect'));
        app('view')->share('favoriteIds', $favoriteIds);

        $response = $next($request);

        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            return $response;
        }

        if ($response instanceof Responsable) {
            return $response->toResponse($request);
        }

        if ($response instanceof JsonResponse) {
            return $response;
        }

        if (is_array($response)) {
            return new JsonResponse($response);
        }

        return new Response((string) $response);
    }
}
