<?php

use App\Models\Anime;
use App\Models\WatchProgress;
use App\Support\Auth;
use App\Support\AnilibriaClient;
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

$router->get('/', function (Request $request) {
    $searchQuery = trim((string) $request->input('search', ''));

    return view('home', [
        'searchQuery' => $searchQuery,
    ])->render();
});

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

$router->get('/watch/{identifier}', function (string $identifier) {
    $query = Anime::query();

    if (ctype_digit($identifier)) {
        $anime = $query->find((int) $identifier);
    } else {
        $anime = $query->where('alias', $identifier)->first();
    }

    if (!$anime) {
        /** @var AnilibriaClient $client */
        $client = app(AnilibriaClient::class);
        $release = $client->fetchRelease($identifier);

        if ($release) {
            $anime = Anime::updateOrCreate(
                ['id' => $release['id']],
                [
                    'title' => $release['title'],
                    'poster_url' => $release['poster_url'],
                    'type' => $release['type'],
                    'year' => $release['year'],
                    'episodes_total' => $release['episodes_total'],
                    'alias' => $release['alias'],
                ]
            );
        }
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

    $activeEpisode = $episodes[0] ?? null;

    $user = Auth::user();
    if ($user) {
        $progress = WatchProgress::query()
            ->where('user_id', $user->getKey())
            ->where('anime_id', $anime->getKey())
            ->first();

        if ($progress) {
            $progressEpisode = collect($episodes)->firstWhere('number', (int) $progress->episode_number);
            if ($progressEpisode) {
                $activeEpisode = $progressEpisode;
            }
        }
    }

    return view('watch', [
        'anime' => $anime,
        'episodes' => $episodes,
        'activeEpisode' => $activeEpisode,
    ])->render();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('catalog/{category}', 'AnimeController@catalog');
    $router->get('anime/search', 'AnimeController@search');
});

$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->post('/favorites', 'FavoriteController@store');
$router->delete('/favorites/{animeId:[0-9]+}', 'FavoriteController@destroy');
$router->post('/watch-progress', 'WatchProgressController@store');
