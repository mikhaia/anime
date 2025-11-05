<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\Relate;
use App\Support\AnilibriaClient;
use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;


class ViewController extends Controller
{
    public function show(Request $request)
    {
        $anime = Anime::find($request->id);
        $lastEpisode = null;
        if ($anime) {
            $lastEpisode = $anime->episodes->last();
        }


        if (!$lastEpisode || $anime->updated_at > $lastEpisode->updated_at) {
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
            $anime->load(['episodes', 'streams', 'relates']);
        }

        $qualities = [];
        foreach ($anime->streams as $stream) {
            $qualities[$stream->quality] = $stream->quality;
        }

        $favorited = false;
        $currentUser = Auth::user();
        if ($currentUser) {
            $favorited = Favorite::where('user_id', $currentUser->id)
                ->where('anime_id', $anime->id)
                ->exists();
        }

        return view('lite.watch', [
            'anime' => $anime,
            'qualities' => $qualities,
            'favorited' => $favorited,
        ]);
    }

    private function syncEpisodes($anime, $episodes)
    {
        // dd($anime);
        // dd($episodes);
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
