<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\AnimeCatalogCache;
use App\Support\AnilibriaClient;
use App\Support\PosterStorage;
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
            $anime = $this->persistAnimeRelease($release);

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
            $anime = $this->persistAnimeRelease($release);

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

    public function suggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));

        if ($query === '') {
            return response()->json([
                'data' => [],
            ]);
        }

        $escapedQuery = $this->escapeLike($query);

        $suggestions = Anime::query()
            ->select(['id', 'title', 'title_english', 'alias'])
            ->where(function ($builder) use ($escapedQuery) {
                $builder
                    ->where('title', 'like', '%' . $escapedQuery . '%')
                    ->orWhere('title_english', 'like', '%' . $escapedQuery . '%');
            })
            ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$escapedQuery . '%'])
            ->orderByRaw('CASE WHEN title_english LIKE ? THEN 0 ELSE 1 END', [$escapedQuery . '%'])
            ->orderBy('title')
            ->limit(8)
            ->get()
            ->map(function (Anime $anime) {
                return [
                    'id' => (int) $anime->getKey(),
                    'title' => $anime->title,
                    'title_english' => $anime->title_english,
                    'alias' => $anime->alias,
                ];
            })
            ->values();

        return response()->json([
            'data' => $suggestions,
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
            'title_english' => $anime->title_english,
            'poster' => $this->buildPosterUrl($anime),
            'poster_url' => $this->buildPosterUrl($anime),
            'type' => $anime->type,
            'year' => $anime->year !== null ? (int) $anime->year : null,
            'episodes_total' => $anime->episodes_total !== null ? (int) $anime->episodes_total : null,
            'alias' => $anime->alias,
        ];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function persistAnimeRelease(array $release): Anime
    {
        $id = (int) ($release['id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('Release identifier must be a positive integer.');
        }

        $existing = Anime::query()->find($id);

        $englishTitle = $this->normalizeEnglishTitle($release['title_english'] ?? null);

        /** @var PosterStorage $posterStorage */
        $posterStorage = app(PosterStorage::class);

        $existingPoster = $existing?->poster;
        $existingRemote = $existing?->getRawOriginal('poster_url');

        $posterPath = $posterStorage->store(
            $release['poster_url'] ?? null,
            $existingPoster,
            $existingRemote,
            $id
        );

        $posterSource = $posterStorage->resolvePosterUrl(
            $release['poster_url'] ?? null,
            $existingRemote
        );

        return Anime::updateOrCreate(
            ['id' => $id],
            [
                'title' => $release['title'] ?? 'Неизвестное аниме',
                'title_english' => $englishTitle,
                'poster_url' => $posterSource,
                'poster' => $posterPath,
                'type' => $release['type'] ?? null,
                'year' => $release['year'] ?? null,
                'episodes_total' => $release['episodes_total'] ?? null,
                'alias' => $release['alias'] ?? null,
            ]
        );
    }

    private function normalizeEnglishTitle($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function buildPosterUrl(Anime $anime): ?string
    {
        /** @var PosterStorage $posterStorage */
        $posterStorage = app(PosterStorage::class);

        $public = $posterStorage->buildPublicUrl($anime->poster);
        if ($public !== null) {
            return $public;
        }

        $source = $anime->getRawOriginal('poster_url');

        return is_string($source) && trim($source) !== '' ? $source : null;
    }
}
