<?php

use App\Models\Anime;
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

$router->get('/watch/{identifier}', function (string $identifier) {
    $query = Anime::query();

    if (ctype_digit($identifier)) {
        $anime = $query->find((int) $identifier);
    } else {
        $anime = $query->where('alias', $identifier)->first();
    }

    if (!$anime) {
        abort(404);
    }

    $episodesTotal = (int) ($anime->episodes_total ?? 0);
    $episodesCount = max(1, min($episodesTotal > 0 ? $episodesTotal : 12, 12));

    $episodes = [];
    $baseStream = 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8';

    for ($episodeNumber = 1; $episodeNumber <= $episodesCount; $episodeNumber++) {
        $episodes[] = [
            'number' => $episodeNumber,
            'title' => sprintf('Серия %02d', $episodeNumber),
            'description' => sprintf(
                '«%s» — эпизод %02d. Наслаждайтесь просмотром любимого тайтла в высоком качестве.',
                $anime->title,
                $episodeNumber
            ),
            'duration' => '24 мин.',
            'stream_url' => $baseStream,
        ];
    }

    return view('watch', [
        'anime' => $anime,
        'episodes' => $episodes,
        'activeEpisode' => $episodes[0] ?? null,
    ])->render();
});

$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->post('/favorites', 'FavoriteController@store');
$router->delete('/favorites/{animeId:[0-9]+}', 'FavoriteController@destroy');
