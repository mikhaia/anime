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

$router->get('/profile', 'ProfileController@show');
$router->post('/profile', 'ProfileController@update');

$router->get('/watch/{identifier}', function (string $identifier) {
    $query = Anime::query();

    if (ctype_digit($identifier)) {
        $anime = $query->find((int) $identifier);
    } else {
        $anime = $query->where('alias', $identifier)->first();
    }

    /** @var AnilibriaClient $client */
    $client = app(AnilibriaClient::class);

    $releaseIdentifier = $identifier;
    if ($anime) {
        $releaseIdentifier = $anime->alias ?: (string) $anime->getKey();
    }

    $release = $client->fetchRelease($releaseIdentifier, true);

    if (!$release && $anime) {
        $release = $client->fetchRelease((string) $anime->getKey(), true);
    }

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

    if (!$anime) {
        abort(404);
    }

    $formatDuration = static function (?int $seconds): string {
        if ($seconds === null || $seconds <= 0) {
            return '—';
        }

        $minutes = (int) round($seconds / 60);
        $minutes = max($minutes, 1);

        return sprintf('%d мин.', $minutes);
    };

    $buildDescription = static function (string $animeTitle, int $episodeNumber, string $episodeTitle): string {
        $normalizedTitle = trim($animeTitle) !== '' ? $animeTitle : 'Аниме';
        $normalizedEpisodeTitle = trim($episodeTitle) !== ''
            ? $episodeTitle
            : sprintf('Серия %02d', $episodeNumber);

        return sprintf(
            '«%s» — эпизод %02d «%s». Наслаждайтесь просмотром любимого тайтла в высоком качестве.',
            $normalizedTitle,
            $episodeNumber,
            $normalizedEpisodeTitle
        );
    };

    $episodes = [];
    $seasons = [];

    if ($release && !empty($release['episodes'])) {
        foreach ($release['episodes'] as $episode) {
            $streams = [];
            foreach (($episode['streams'] ?? []) as $quality => $url) {
                if (is_string($quality) && is_string($url) && $quality !== '' && $url !== '') {
                    $streams[$quality] = $url;
                }
            }

            if (empty($streams)) {
                continue;
            }

            $defaultQuality = $episode['default_quality'] ?? null;
            $defaultStream = null;

            if (is_string($defaultQuality) && isset($streams[$defaultQuality])) {
                $defaultStream = $streams[$defaultQuality];
            } else {
                $firstQuality = array_key_first($streams);
                if ($firstQuality !== null) {
                    $defaultStream = $streams[$firstQuality];
                    $defaultQuality = $firstQuality;
                }
            }

            if ($defaultStream === null) {
                continue;
            }

            $episodeNumber = (int) ($episode['number'] ?? 0);
            if ($episodeNumber <= 0) {
                continue;
            }

            $episodeTitle = (string) ($episode['title'] ?? sprintf('Серия %02d', $episodeNumber));

            $episodes[] = [
                'number' => $episodeNumber,
                'title' => $episodeTitle,
                'description' => $buildDescription($anime->title ?? '', $episodeNumber, $episodeTitle),
                'duration' => $formatDuration($episode['duration_seconds'] ?? null),
                'stream_url' => $defaultStream,
                'streams' => $streams,
                'default_quality' => $defaultQuality,
            ];
        }

        usort($episodes, static fn (array $left, array $right) => $left['number'] <=> $right['number']);
    }

    if (empty($episodes)) {
        $episodesTotal = (int) ($anime->episodes_total ?? 0);
        $episodesCount = max(1, min($episodesTotal > 0 ? $episodesTotal : 12, 12));

        $baseStream = 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8';

        for ($episodeNumber = 1; $episodeNumber <= $episodesCount; $episodeNumber++) {
            $title = sprintf('Серия %02d', $episodeNumber);

            $episodes[] = [
                'number' => $episodeNumber,
                'title' => $title,
                'description' => $buildDescription($anime->title ?? '', $episodeNumber, $title),
                'duration' => '24 мин.',
                'stream_url' => $baseStream,
                'streams' => [
                    '720p' => $baseStream,
                ],
                'default_quality' => '720p',
            ];
        }
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

    $currentReleaseId = (int) $anime->getKey();
    $potentialIdentifiers = array_values(array_filter([
        is_string($anime->alias ?? null) && trim($anime->alias) !== '' ? $anime->alias : null,
        $currentReleaseId > 0 ? (string) $currentReleaseId : null,
    ], static fn ($value) => is_string($value) && $value !== ''));

    $relatedSeasons = [];
    if (is_array($release) && !empty($release['related']) && is_array($release['related'])) {
        $relatedSeasons = $release['related'];
    }

    $seasonsById = [];

    foreach ($relatedSeasons as $relatedSeason) {
        $releaseId = isset($relatedSeason['id']) ? (int) $relatedSeason['id'] : null;
        if (!$releaseId || $releaseId <= 0) {
            continue;
        }

        if ($releaseId === $currentReleaseId) {
            continue;
        }

        $identifier = $relatedSeason['identifier'] ?? ($relatedSeason['alias'] ?? null);
        if (is_string($identifier)) {
            $identifier = trim($identifier);
        }

        if (!is_string($identifier) || $identifier === '') {
            $identifier = (string) $releaseId;
        }

        $title = is_string($relatedSeason['title'] ?? null) && trim($relatedSeason['title']) !== ''
            ? $relatedSeason['title']
            : 'Сезон';

        $relation = is_string($relatedSeason['relation'] ?? null) && trim($relatedSeason['relation']) !== ''
            ? trim($relatedSeason['relation'])
            : null;

        $isActive = in_array($identifier, $potentialIdentifiers, true) || (string) $releaseId === ($potentialIdentifiers[1] ?? null);

        $seasonsById[$releaseId] = [
            'title' => $title,
            'identifier' => $identifier,
            'relation' => $relation,
            'is_active' => $isActive,
        ];
    }

    $seasons = array_values($seasonsById);

    $hasActiveSeason = false;
    foreach ($seasons as &$season) {
        if (!empty($season['is_active'])) {
            $hasActiveSeason = true;
            $season['is_active'] = true;
            $season['title'] = is_string($anime->title ?? null) && trim($anime->title) !== ''
                ? $anime->title
                : $season['title'];
            if (!empty($potentialIdentifiers)) {
                $season['identifier'] = $potentialIdentifiers[0];
            }
            if (!is_string($season['relation'] ?? null) || trim((string) $season['relation']) === '') {
                $season['relation'] = 'Текущий сезон';
            }
            break;
        }
    }
    unset($season);

    if (!$hasActiveSeason) {
        $fallbackIdentifier = $potentialIdentifiers[0] ?? ($potentialIdentifiers[1] ?? ($currentReleaseId > 0 ? (string) $currentReleaseId : null));

        if (is_string($fallbackIdentifier) && $fallbackIdentifier !== '') {
            array_unshift($seasons, [
                'title' => is_string($anime->title ?? null) && trim($anime->title) !== ''
                    ? $anime->title
                    : 'Текущий сезон',
                'identifier' => $fallbackIdentifier,
                'relation' => 'Текущий сезон',
                'is_active' => true,
            ]);
        }
    }

    return view('watch', [
        'anime' => $anime,
        'episodes' => $episodes,
        'activeEpisode' => $activeEpisode,
        'seasons' => $seasons,
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
$router->get('/switch-user', 'AuthController@switchUser');
$router->post('/favorites', 'FavoriteController@store');
$router->delete('/favorites/{animeId:[0-9]+}', 'FavoriteController@destroy');
$router->post('/watch-progress', 'WatchProgressController@store');
