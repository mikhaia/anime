<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\AnimeCatalogCache;
use App\Support\AnilibriaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

class AnimeController extends Controller
{
    private const SORTING = [
        'top' => 'RATING_DESC',
        'new' => 'FRESH_AT_DESC',
    ];

    public function catalog(Request $request, string $category): JsonResponse
    {
        $category = strtolower($category);
        $sorting = self::SORTING[$category] ?? null;
        if ($sorting === null) {
            return response()->json([
                'message' => 'Категория не найдена.',
            ], 404);
        }

        $page = max(1, (int) $request->query('page', 1));
        $today = CarbonImmutable::today();

        $cache = AnimeCatalogCache::query()
            ->where('category', $category)
            ->where('page', $page)
            ->first();

        if ($cache && $cache->cached_date && $cache->cached_date->isSameDay($today)) {
            $data = $this->loadCachedAnime($cache->anime_ids ?? []);

            return response()->json([
                'data' => $data,
                'meta' => [
                    'page' => $page,
                    'has_next_page' => (bool) $cache->has_next_page,
                    'cached' => true,
                ],
            ]);
        }

        /** @var AnilibriaClient $client */
        $client = app(AnilibriaClient::class);
        $result = $client->fetchCatalogPage($sorting, $page);

        if (!Arr::get($result, 'success')) {
            return response()->json([
                'message' => 'Не удалось получить данные от сервера аниме.',
            ], 502);
        }

        $items = [];
        $ids = [];

        foreach (Arr::get($result, 'items', []) as $release) {
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

            $ids[] = $anime->getKey();
            $items[] = $this->formatAnime($anime);
        }

        AnimeCatalogCache::updateOrCreate(
            [
                'category' => $category,
                'page' => $page,
            ],
            [
                'anime_ids' => $ids,
                'cached_date' => $today,
                'has_next_page' => (bool) Arr::get($result, 'has_next_page', false),
            ]
        );

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $page,
                'has_next_page' => (bool) Arr::get($result, 'has_next_page', false),
                'cached' => false,
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));
        $page = max(1, (int) $request->query('page', 1));

        if ($query === '') {
            return response()->json([
                'data' => [],
                'meta' => [
                    'page' => 1,
                    'has_next_page' => false,
                ],
            ]);
        }

        /** @var AnilibriaClient $client */
        $client = app(AnilibriaClient::class);
        $result = $client->searchReleases($query, $page);

        if (!Arr::get($result, 'success')) {
            return response()->json([
                'message' => 'Не удалось получить данные от сервера аниме.',
            ], 502);
        }

        $items = [];
        foreach (Arr::get($result, 'items', []) as $release) {
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

            $items[] = $this->formatAnime($anime);
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $page,
                'has_next_page' => (bool) Arr::get($result, 'has_next_page', false),
            ],
        ]);
    }

    private function loadCachedAnime(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $animeCollection = Anime::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($ids as $id) {
            $anime = $animeCollection->get($id);
            if ($anime) {
                $items[] = $this->formatAnime($anime);
            }
        }

        return $items;
    }

    private function formatAnime(Anime $anime): array
    {
        return [
            'id' => (int) $anime->getKey(),
            'title' => $anime->title,
            'poster_url' => $anime->poster_url,
            'type' => $anime->type,
            'year' => $anime->year !== null ? (int) $anime->year : null,
            'episodes_total' => $anime->episodes_total !== null ? (int) $anime->episodes_total : null,
            'alias' => $anime->alias,
        ];
    }
}
