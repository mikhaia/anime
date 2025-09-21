<?php

namespace Tests;

use App\Models\Anime;
use App\Models\AnimeCatalogCache;
use App\Support\AnilibriaClient;
use App\Support\PosterStorage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Laravel\Lumen\Testing\DatabaseMigrations;

class AnimeCatalogCacheTest extends TestCase
{
    use DatabaseMigrations;

    private object $clientStub;

    protected function setUp(): void
    {
        parent::setUp();

        $client = new class extends AnilibriaClient {
            public array $catalogResponses = [];
            public int $calls = 0;

            public function fetchCatalogPage(string $sorting, int $page = 1): array
            {
                $this->calls++;

                if (isset($this->catalogResponses[$sorting][$page])) {
                    return $this->catalogResponses[$sorting][$page];
                }

                return [
                    'success' => true,
                    'has_next_page' => false,
                    'items' => [
                        [
                            'id' => 101,
                            'title' => 'Test Anime',
                            'poster_url' => 'https://example.com/poster.jpg',
                            'type' => 'TV',
                            'year' => 2024,
                            'episodes_total' => 12,
                            'alias' => 'test-anime',
                        ],
                    ],
                ];
            }

            public function fetchRelease(string $identifier, bool $withEpisodes = false): ?array
            {
                return null;
            }

            public function searchReleases(string $query, int $page = 1): array
            {
                return [
                    'success' => true,
                    'has_next_page' => false,
                    'items' => [],
                ];
            }

        };

        $this->clientStub = $client;
        $this->app->instance(AnilibriaClient::class, $client);

        $posterStorage = new class {
            public function store($source, $existingPoster, $existingRemote, $animeId)
            {
                if (!is_string($source) || trim($source) === '') {
                    return $existingPoster;
                }

                $trimmed = trim($source);
                if (str_starts_with($trimmed, '/data/posters/')) {
                    return ltrim($trimmed, '/');
                }

                if (str_starts_with($trimmed, 'data/posters/')) {
                    return $trimmed;
                }

                return sprintf('data/posters/%d-test.jpg', (int) $animeId);
            }

            public function resolvePosterUrl($source, $existingRemote)
            {
                if (!is_string($source) || trim($source) === '') {
                    return $existingRemote;
                }

                $trimmed = trim($source);
                if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
                    return $trimmed;
                }

                return $existingRemote;
            }

            public function buildPublicUrl($poster)
            {
                if (!is_string($poster) || trim($poster) === '') {
                    return null;
                }

                $trimmed = trim($poster);

                return '/' . ltrim($trimmed, '/');
            }
        };

        $this->app->instance(PosterStorage::class, $posterStorage);
    }

    public function test_catalog_response_is_cached(): void
    {
        $this->get('/api/catalog/top');

        $this->seeStatusCode(200);

        $cache = AnimeCatalogCache::query()
            ->where('category', 'top')
            ->where('page', 1)
            ->first();

        $this->assertNotNull($cache);
        $this->assertSame([101], $cache->anime_ids);
        $this->assertTrue($cache->cached_date->isSameDay(CarbonImmutable::today()));
        $this->assertFalse($cache->has_next_page);
        $this->assertSame(1, $this->clientStub->calls);
    }

    public function test_catalog_uses_cache_on_same_day(): void
    {
        Anime::query()->create([
            'id' => 202,
            'title' => 'Cached Anime',
            'poster_url' => 'https://example.com/poster2.jpg',
            'type' => 'Movie',
            'year' => 2023,
            'episodes_total' => 1,
            'alias' => 'cached-anime',
        ]);

        AnimeCatalogCache::query()->create([
            'category' => 'new',
            'page' => 1,
            'anime_ids' => [202],
            'cached_date' => CarbonImmutable::today()->toDateString(),
            'has_next_page' => true,
        ]);

        $initialCalls = $this->clientStub->calls;

        $this->get('/api/catalog/new');

        $this->seeStatusCode(200);

        $payload = json_decode($this->response->getContent(), true);
        $this->assertSame([202], Arr::pluck($payload['data'], 'id'));
        $this->assertTrue((bool) Arr::get($payload, 'meta.cached'));
        $this->assertTrue((bool) Arr::get($payload, 'meta.has_next_page'));
        $this->assertSame($initialCalls, $this->clientStub->calls, 'Catalog client should not be called when cache is valid');
    }
}
