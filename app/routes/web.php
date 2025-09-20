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

$router->get('/', 'HomeController@index');

$router->get('/list', function (Request $request) {
    $mode = $request->input('mode', 'favorites');
    $favorites = collect();
    $searchQuery = trim((string) $request->input('search', ''));

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
        'searchQuery' => $searchQuery,
    ])->render();
});

$router->get('/details', function () {
    return view('details')->render();
});

$router->get('/profile', 'ProfileController@show');
$router->post('/profile', 'ProfileController@update');

$router->get('/watch/{identifier}', 'WatchController@show');

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('catalog/{category}', 'AnimeController@catalog');
    $router->get('anime/search', 'AnimeController@search');
    $router->get('anime/suggestions', 'AnimeController@suggestions');
});

$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->get('/switch-user', 'AuthController@switchUser');
$router->post('/favorites', 'FavoriteController@store');
$router->delete('/favorites/{animeId:[0-9]+}', 'FavoriteController@destroy');
$router->post('/watch-progress', 'WatchProgressController@store');
