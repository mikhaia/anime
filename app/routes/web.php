<?php

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
    return view('list', ['mode' => $request->input('mode', 'favorites')])->render();
});

$router->get('/details', function () {
    return view('details')->render();
});

$router->get('/watch', function () {
    return view('watch')->render();
});
