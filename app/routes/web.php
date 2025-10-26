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

$router->get('/list', 'HomeController@list');

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
$router->post('/switch-user/login', 'AuthController@loginFromDevice');
$router->post('/favorites', 'FavoriteController@store');
$router->delete('/favorites/{animeId:[0-9]+}', 'FavoriteController@destroy');
$router->post('/watch-progress', 'WatchProgressController@store');

// Lite Routes
$router->get('/new', 'ListController@new');
$router->get('/top', 'ListController@top');
$router->get('/search', 'ListController@search');
$router->get('/anime/{id}', 'ViewController@show');
$router->get('/fav', 'ListController@fav');
$router->get('/genre/{genre:[0-9]+}', 'ListController@genre');
