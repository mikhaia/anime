<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\Relate;
use App\Support\AnilibriaClient;


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
        }

        $qualities = [];
        foreach ($anime->streams as $stream) {
            $qualities[$stream->quality] = $stream->quality;
        }

        return view('lite.watch', [
            'anime' => $anime,
            'qualities' => $qualities
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
