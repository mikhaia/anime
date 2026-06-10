<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\Relate;
use App\Models\WatchProgress;
use App\Support\AnilibriaClient;
use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;


class ViewController extends Controller
{
    private const STREAM_CACHE_DAYS = 30;

    public function show(Request $request)
    {
        $anime = Anime::find($request->id);
        $lastEpisode = null;
        if ($anime) {
            $lastEpisode = $anime->episodes->last();
        }

        $streamsOutdated = $anime
            && $anime->streams()
                ->where(function ($query) {
                    $query->whereNull('cached_at')
                        ->orWhere('cached_at', '<', now()->subDays(self::STREAM_CACHE_DAYS));
                })
                ->exists();

        if (!$lastEpisode || $anime->updated_at > $lastEpisode->updated_at || $streamsOutdated) {
            $client = app(AnilibriaClient::class);
            $release = $client->fetchDetails($request->id);
            if (!$anime) {
                $client->updateAnime($release);
                $anime = Anime::find($request->id);
            }

            if ($release) {
                $this->syncEpisodes($anime, $release['episodes']);
                $this->syncRelated($anime->id, $release['related']);
            }

            $anime->refresh();
            $anime->load(['episodes', 'streams', 'relates', 'torrents']);
        }

        $anime->loadMissing(['episodes', 'streams', 'relates', 'torrents']);

        $qualities = [];
        foreach ($anime->streams as $stream) {
            $qualities[$stream->quality] = $stream->quality;
        }
        $qualities = array_reverse($qualities);

        $favorited = false;
        $currentUser = Auth::user();
        if ($currentUser) {
            $favorited = Favorite::where('user_id', $currentUser->id)
                ->where('anime_id', $anime->id)
                ->exists();
        }

        $progress = null;
        if ($currentUser) {
            $progress = WatchProgress::where('user_id', $currentUser->id)
                ->where('anime_id', $anime->id)
                ->first();
        }

        return view('lite.watch', [
            'anime' => $anime,
            'qualities' => $qualities,
            'favorited' => $favorited,
            'progress' => $progress,
        ]);
    }

    private function syncEpisodes($anime, $episodes)
    {
        foreach ($episodes as $episode) {
            Episode::updateOrCreate(
                [
                    'anime_id' => $anime->id,
                    'number' => $episode['number'],
                ],
                [
                    'title' => $episode['title'] ?? '',
                    'duration' => $episode['duration_seconds'],
                ]
            );

            foreach ($episode['streams'] as $quality => $url) {
                if (!$url) continue;
                $anime->streams()->updateOrCreate(
                    [
                        'episode_id' => $episode['number'],
                        'quality' => $quality,
                    ],
                    [
                        'url' => $url,
                        'cached_at' => now(),
                    ]
                );
            }
        }
    }

    private function syncRelated($animeId, $related)
    {
        foreach ($related as $item) {
            Relate::updateOrCreate(
                [
                    'anime_id' => $animeId,
                    'relate_id' => $item['id'],
                ],
                [
                    'title' => $item['title'],
                    'title_english' => $item['title_english'],
                    'alias' => $item['alias'],
                ]
            );
        }
    }
}
