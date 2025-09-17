<?php

use App\Support\Auth;
use Illuminate\Http\Request;

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/*
$router->get('/', function () use ($router) {
    return $router->app->version();
});
*/

$router->get('/', function () {
    return view('home')->render();
});

$router->get('/list', function (Request $request) {
    $mode = $request->input('mode', 'favorites');
    $favorites = collect();

    if ($mode === 'favorites') {
        $user = Auth::user();
        if ($user) {
            $favorites = $user->favorites()
                ->with('anime')
                ->orderByDesc('created_at')
                ->get();
        }
    }

    return view('list', [
        'mode' => $mode,
        'favorites' => $favorites,
    ])->render();
});

$router->get('/details', function () {
    return view('details')->render();
});

$router->get('/watch', function () {
    return view('watch')->render();
});

$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->post('/favorites', 'FavoriteController@store');
$router->delete('/favorites/{animeId:[0-9]+}', 'FavoriteController@destroy');
