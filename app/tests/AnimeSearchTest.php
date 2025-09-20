<?php

namespace Tests;

use App\Models\Anime;
use App\Support\AnilibriaClient;
use Illuminate\Support\Arr;
use Laravel\Lumen\Testing\DatabaseMigrations;

class AnimeSearchTest extends TestCase
{
    use DatabaseMigrations;

    private object $clientStub;

    protected function setUp(): void
    {
        parent::setUp();

        $client = new class extends AnilibriaClient {
            public int $searchCalls = 0;
            public array $searchResponses = [];

            public function fetchCatalogPage(string $sorting, int $page = 1): array
            {
                return [
                    'success' => true,
                    'has_next_page' => false,
                    'items' => [],
                ];
            }

            public function fetchRelease(string $identifier, bool $withEpisodes = false): ?array
            {
                return null;
            }

            public function searchReleases(string $query, int $page = 1): array
            {
                $this->searchCalls++;

                if (isset($this->searchResponses[$query][$page])) {
                    return $this->searchResponses[$query][$page];
                }

                return [
                    'success' => true,
                    'has_next_page' => false,
                    'items' => [],
                ];
            }
        };

        $client->searchResponses = [
            'spy' => [
                1 => [
                    'success' => true,
                    'has_next_page' => true,
                    'items' => [
                        [
                            'id' => 555,
                            'title' => 'Spy Family',
                            'title_english' => 'Spy x Family',
                            'poster_url' => 'https://example.com/poster.jpg',
                            'type' => 'TV',
                            'year' => 2022,
                            'episodes_total' => 12,
                            'alias' => 'spy-family',
                        ],
                    ],
                ],
            ],
        ];

        $this->clientStub = $client;
        $this->app->instance(AnilibriaClient::class, $client);
    }

    public function test_search_results_are_persisted(): void
    {
        $this->get('/api/anime/search?query=spy');

        $this->seeStatusCode(200);

        $payload = json_decode($this->response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([555], Arr::pluck($payload['data'], 'id'));
        $this->assertTrue((bool) Arr::get($payload, 'meta.has_next_page'));
        $this->assertSame(1, $this->clientStub->searchCalls);

        $anime = Anime::query()->find(555);
        $this->assertNotNull($anime);
        $this->assertSame('Spy Family', $anime->title);
        $this->assertSame('Spy x Family', $anime->title_english);
        $this->assertSame('https://example.com/poster.jpg', $anime->poster_url);
        $this->assertSame('TV', $anime->type);
        $this->assertSame(2022, $anime->year);
        $this->assertSame(12, $anime->episodes_total);
        $this->assertSame('spy-family', $anime->alias);
    }

    public function test_search_updates_existing_record(): void
    {
        Anime::query()->create([
            'id' => 555,
            'title' => 'Old Title',
            'title_english' => null,
            'poster_url' => 'https://example.com/old.jpg',
            'type' => 'Movie',
            'year' => 2020,
            'episodes_total' => 1,
            'alias' => 'old-title',
        ]);

        $this->clientStub->searchResponses = [
            'spy' => [
                1 => [
                    'success' => true,
                    'has_next_page' => false,
                    'items' => [
                        [
                            'id' => 555,
                            'title' => 'Updated Spy Family',
                            'title_english' => 'Updated Spy x Family',
                            'poster_url' => 'https://example.com/new.jpg',
                            'type' => 'Special',
                            'year' => 2023,
                            'episodes_total' => 3,
                            'alias' => 'updated-spy-family',
                        ],
                    ],
                ],
            ],
        ];

        $this->get('/api/anime/search?query=spy');

        $this->seeStatusCode(200);

        $anime = Anime::query()->find(555);
        $this->assertNotNull($anime);
        $this->assertSame('Updated Spy Family', $anime->title);
        $this->assertSame('Updated Spy x Family', $anime->title_english);
        $this->assertSame('https://example.com/new.jpg', $anime->poster_url);
        $this->assertSame('Special', $anime->type);
        $this->assertSame(2023, $anime->year);
        $this->assertSame(3, $anime->episodes_total);
        $this->assertSame('updated-spy-family', $anime->alias);
        $this->assertSame(1, Anime::query()->count());
    }
}
