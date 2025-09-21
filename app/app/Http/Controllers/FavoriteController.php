<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Favorite;
use App\Support\Auth;
use App\Support\PosterStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
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
            'id' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'title_english' => 'nullable|string|max:255',
            'poster' => 'nullable|string|max:1024',
            'type' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:0',
            'episodes' => 'nullable|integer|min:0',
            'alias' => 'nullable|string|max:255',
        ]);

        $existing = Anime::query()->find((int) $data['id']);

        /** @var PosterStorage $posterStorage */
        $posterStorage = app(PosterStorage::class);

        $posterPath = $posterStorage->store(
            $data['poster'] ?? null,
            $existing?->poster,
            $existing?->getRawOriginal('poster_url'),
            (int) $data['id']
        );

        $posterSource = $posterStorage->resolvePosterUrl(
            $data['poster'] ?? null,
            $existing?->getRawOriginal('poster_url')
        );

        $anime = Anime::updateOrCreate(
            ['id' => $data['id']],
            [
                'title' => $data['title'],
                'title_english' => $this->normalizeEnglishTitle($data['title_english'] ?? null),
                'poster_url' => $posterSource,
                'poster' => $posterPath,
                'type' => $data['type'] ?? null,
                'year' => $data['year'] ?? null,
                'episodes_total' => $data['episodes'] ?? null,
                'alias' => $data['alias'] ?? null,
            ]
        );

        $favorite = Favorite::firstOrCreate([
            'user_id' => $user->getKey(),
            'anime_id' => $anime->getKey(),
        ]);

        return response()->json([
            'status' => 'added',
            'favorite' => [
                'id' => $favorite->anime_id,
                'title' => $anime->title,
                'title_english' => $anime->title_english,
            ],
        ]);
    }

    public function destroy(int $animeId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Требуется авторизация.',
            ], 401);
        }

        $favorite = $user->favorites()->where('anime_id', $animeId)->first();
        if (!$favorite) {
            return response()->json([
                'status' => 'missing',
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'status' => 'removed',
        ]);
    }

    private function normalizeEnglishTitle($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
