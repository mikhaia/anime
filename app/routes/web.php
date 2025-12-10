<?php

use App\Http\Controllers\AnimeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViewController;
use App\Http\Controllers\WatchController;
use App\Http\Controllers\WatchProgressController;
use Illuminate\Support\Facades\Route;

// Route::get('/', [HomeController::class, 'index']);
Route::get('/', [ListController::class, 'search']);
Route::get('/list', [HomeController::class, 'list']);
Route::get('/details', fn() => view('details'));

Route::get('/profile', [ProfileController::class, 'show']);
Route::post('/profile', [ProfileController::class, 'update']);

Route::get('/watch/{identifier}', [WatchController::class, 'show']);

Route::prefix('api')->group(function () {
    Route::get('catalog/{category}', [AnimeController::class, 'catalog']);
    Route::get('anime/search', [AnimeController::class, 'search']);
    Route::get('anime/suggestions', [AnimeController::class, 'suggestions']);
    Route::get('search-suggestions', [ListController::class, 'searchSuggestions']);
});

Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/switch-user', [AuthController::class, 'switchUser']);
Route::post('/switch-user/login', [AuthController::class, 'loginFromDevice']);
Route::post('/favorites', [FavoriteController::class, 'store']);
Route::delete('/favorites/{animeId}', [FavoriteController::class, 'destroy'])
    ->whereNumber('animeId');
Route::post('/watch-progress', [WatchProgressController::class, 'store']);

Route::get('/new', [ListController::class, 'new']);
Route::get('/top', [ListController::class, 'top']);
Route::get('/search', [ListController::class, 'search']);
Route::get('/anime/{id}', [ViewController::class, 'show']);
Route::get('/fav', [ListController::class, 'fav']);
Route::get('/genre/{genre}', [ListController::class, 'genre'])
    ->whereNumber('genre');

Route::post('/lite/login', [UserController::class, 'login']);
Route::post('/lite/create', [UserController::class, 'create']);
Route::get('/test', [UserController::class, 'test']);
Route::post('/favorite', [UserController::class, 'favorite']);
Route::get('/users', [UserController::class, 'index']);
Route::get('/switch/{id}', [UserController::class, 'switch'])
    ->whereNumber('id');
