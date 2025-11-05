<?php

namespace App\Support;

use App\Models\Anime;
use App\Models\AnimeCatalogCache;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AnimeCatalogService
{
    private const SORTING = [
        'top' => 'RATING_DESC',
        'new' => 'FRESH_AT_DESC',
    ];

    public function __construct(
        private readonly AnilibriaClient $client,
        private readonly PosterStorage $posterStorage,
    ) {
    }

    /**
     * @return array{category:string,page:int,items:Collection<int,Anime>,has_next_page:bool,cached:bool,failed:bool,valid:bool}
     */
    public function getCatalogPage(string $category, int $page = 1): array
    {
        $normalizedCategory = strtolower($category);
        $sorting = self::SORTING[$normalizedCategory] ?? null;

        if ($sorting === null) {
            return [
                'category' => $normalizedCategory,
                'page' => max(1, $page),
                'items' => collect(),
                'has_next_page' => false,
                'cached' => false,
                'failed' => false,
                'valid' => false,
            ];
        }

        $page = max(1, $page);
        $today = CarbonImmutable::today();

        $cache = AnimeCatalogCache::query()
            ->where('category', $normalizedCategory)
            ->where('page', $page)
            ->first();

        if ($cache && $cache->cached_date && $cache->cached_date->isSameDay($today)) {
            return [
                'category' => $normalizedCategory,
                'page' => $page,
                'items' => $this->loadCachedAnime($cache->anime_ids ?? []),
                'has_next_page' => (bool) $cache->has_next_page,
                'cached' => true,
                'failed' => false,
                'valid' => true,
            ];
        }

        $result = $this->client->fetchCatalogPage($sorting, $page);

        if (!Arr::get($result, 'success')) {
            $items = $cache ? $this->loadCachedAnime($cache->anime_ids ?? []) : collect();

            return [
                'category' => $normalizedCategory,
                'page' => $page,
                'items' => $items,
                'has_next_page' => (bool) ($cache?->has_next_page ?? false),
                'cached' => false,
                'failed' => true,
                'valid' => true,
            ];
        }

        $items = collect();
        $ids = [];

        foreach (Arr::get($result, 'items', []) as $release) {
            $anime = $this->persistAnimeRelease((array) $release);
            $ids[] = $anime->getKey();
            $items->push($anime);
        }

        AnimeCatalogCache::updateOrCreate(
            [
                'category' => $normalizedCategory,
                'page' => $page,
            ],
            [
                'anime_ids' => $ids,
                'cached_date' => $today,
                'has_next_page' => (bool) Arr::get($result, 'has_next_page', false),
            ]
        );

        return [
            'category' => $normalizedCategory,
            'page' => $page,
            'items' => $items,
            'has_next_page' => (bool) Arr::get($result, 'has_next_page', false),
            'cached' => false,
            'failed' => false,
            'valid' => true,
        ];
    }

    private const DEFAULT_PER_PAGE = 15;

    public function buildCatalogPaginator(array $result, Request $request, int $fallbackPerPage = self::DEFAULT_PER_PAGE): Paginator
    {
        $items = $result['items'] instanceof Collection ? $result['items'] : collect($result['items']);
        $count = $items->count();
        $perPage = $count > 0 ? $count : $fallbackPerPage;
        $page = max(1, (int) ($result['page'] ?? 1));

        $total = ($page - 1) * $perPage + $count;
        if (!empty($result['has_next_page'])) {
            $total += $perPage;
        }

        if ($total <= 0) {
            $total = $count > 0 ? $count : $perPage;
        }

        return new Paginator(
            $items,
            $total,
            max(1, $perPage),
            $page,
            [
                'path' => url('/list'),
                'query' => Arr::except($request->query(), 'page'),
            ]
        );
    }

    public function searchLocal(string $query, int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $query = trim($query);

        if ($query === '') {
            return new Paginator(collect(), 0, $perPage, $page, [
                'path' => url('/api/anime/search'),
                'query' => [],
            ]);
        }

        $remoteResult = $this->client->searchReleases($query, $page);

        if (Arr::get($remoteResult, 'success')) {
            $items = collect();

            foreach (Arr::get($remoteResult, 'items', []) as $release) {
                try {
                    $items->push($this->persistAnimeRelease((array) $release));
                } catch (\Throwable) {
                    // Ignore malformed releases in test stubs or remote payloads.
                }
            }

            $items = $items->filter()->values();

            if ($items->isNotEmpty()) {
                $total = ($page - 1) * $perPage + $items->count();
                if (!empty($remoteResult['has_next_page'])) {
                    $total += $perPage;
                }

                return new Paginator(
                    $items,
                    max($total, $items->count()),
                    $perPage,
                    $page,
                    [
                        'path' => url('/api/anime/search'),
                        'query' => ['query' => $query],
                    ]
                );
            }
        }

        $escaped = $this->escapeLike($query);

        $builder = Anime::query()
            ->where(function ($builder) use ($escaped) {
                $builder
                    ->where('title', 'like', '%' . $escaped . '%')
                    ->orWhere('title_english', 'like', '%' . $escaped . '%');
            })
            ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$escaped . '%'])
            ->orderByRaw('CASE WHEN title_english LIKE ? THEN 0 ELSE 1 END', [$escaped . '%'])
            ->orderBy('title');

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    public function formatAnime(Anime $anime): array
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

    private function loadCachedAnime(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $animeCollection = Anime::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $animeCollection->get($id))
            ->filter()
            ->values();
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

        $posterPath = $this->posterStorage->store(
            $release['poster_url'] ?? null,
            $existing?->poster,
            $existing?->getRawOriginal('poster_url'),
            $id
        );

        $posterSource = $this->posterStorage->resolvePosterUrl(
            $release['poster_url'] ?? null,
            $existing?->getRawOriginal('poster_url')
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
        $public = $this->posterStorage->buildPublicUrl($anime->poster);
        if ($public !== null) {
            return $public;
        }

        $source = $anime->getRawOriginal('poster_url');

        return is_string($source) && trim($source) !== '' ? $source : null;
    }
}
