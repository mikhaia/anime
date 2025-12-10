<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\WatchProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchProgressController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Требуется авторизация.',
            ], 401);
        }

        $data = $this->validate($request, [
            'anime_id' => 'required|integer|min:1',
            'episode_number' => 'required|integer|min:1',
            'time' => 'nullable|integer|min:0',
        ]);

        $anime = Anime::find($data['anime_id']);
        if (!$anime) {
            return response()->json([
                'message' => 'Аниме не найдено.',
            ], 404);
        }

        $episodesTotal = (int) ($anime->episodes_total ?? 0);
        $episodesCount = max(1, min($episodesTotal > 0 ? $episodesTotal : 12, 12));
        $episodeNumber = min($data['episode_number'], $episodesCount);

        $progress = WatchProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'anime_id' => $anime->id,
            ],
            [
                'episode_number' => $episodeNumber,
                'time' => $data['time'] ?? 0,
            ]
        );

        return response()->json([
            'status' => 'saved',
            'progress' => [
                'anime_id' => $progress->anime_id,
                'episode_number' => $progress->episode_number,
                'time' => $progress->time,
            ],
        ]);
    }
}
