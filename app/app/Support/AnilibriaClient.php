<?php

namespace App\Support;

use Illuminate\Support\Arr;

class AnilibriaClient
{
    private const API_BASE_URL = 'https://anilibria.top/api/v1/anime';
    private const BASE_URL = 'https://anilibria.top';
    private const CATALOG_ENDPOINT = '/catalog/releases';
    private const RELEASE_ENDPOINT = '/releases';
    private const FRANCHISE_RELEASE_ENDPOINT = '/franchises/release';

    private const EPISODE_QUALITY_ORDER = [1080, 720, 480, 360, 240];

    public function fetchRelease(string $identifier, bool $withEpisodes = false): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $url = sprintf('%s%s/%s', self::API_BASE_URL, self::RELEASE_ENDPOINT, rawurlencode($identifier));
        $query = [];

        $withRelations = [];

        if ($withEpisodes) {
            $withRelations[] = 'episodes';
        }

        $withRelations[] = 'related';
        $withRelations[] = 'related.release';

        if (!empty($withRelations)) {
            $query['with'] = implode(',', array_unique($withRelations));
        }

        $payload = $this->makeRequest($url, $query);
        if (!is_array($payload) || empty($payload['id'])) {
            return null;
        }

        $title = $this->resolveTitle($payload);
        $posterPath = Arr::get($payload, 'poster.optimized.preview')
            ?? Arr::get($payload, 'poster.optimized.src')
            ?? Arr::get($payload, 'poster.preview')
            ?? Arr::get($payload, 'poster.src');
        $posterUrl = $posterPath ? $this->buildUrl($posterPath) : null;

        $franchiseReleases = $this->fetchFranchiseReleases((int) $payload['id']);
        if (empty($franchiseReleases)) {
            $franchiseReleases = $this->normalizeRelated(Arr::get($payload, 'related', []), (int) $payload['id']);
        }

        return [
            'id' => (int) $payload['id'],
            'title' => $title,
            'poster_url' => $posterUrl,
            'type' => Arr::get($payload, 'type.description'),
            'year' => Arr::get($payload, 'year'),
            'episodes_total' => Arr::get($payload, 'episodes_total'),
            'alias' => Arr::get($payload, 'alias'),
            'episodes' => $withEpisodes
                ? $this->normalizeEpisodes(Arr::get($payload, 'episodes', []))
                : [],
            'related' => $franchiseReleases,
        ];
    }

    public function fetchCatalogPage(string $sorting, int $page = 1): array
    {
        $url = sprintf('%s%s', self::API_BASE_URL, self::CATALOG_ENDPOINT);
        $payload = $this->makeRequest($url, [
            'f[sorting]' => $sorting,
            'page' => max(1, $page),
        ]);

        if (!is_array($payload)) {
            return [
                'items' => [],
                'has_next_page' => false,
                'success' => false,
            ];
        }

        $items = [];
        foreach (Arr::get($payload, 'data', []) as $release) {
            $normalized = $this->normalizeRelease($release);
            if ($normalized) {
                $items[] = $normalized;
            }
        }

        return [
            'items' => $items,
            'has_next_page' => (bool) Arr::get($payload, 'meta.pagination.links.next'),
            'success' => true,
        ];
    }

    public function searchReleases(string $query, int $page = 1): array
    {
        $query = trim($query);
        if ($query === '') {
            return [
                'items' => [],
                'has_next_page' => false,
                'success' => true,
            ];
        }

        $url = sprintf('%s%s', self::API_BASE_URL, self::CATALOG_ENDPOINT);
        $payload = $this->makeRequest($url, [
            'search' => $query,
            'page' => max(1, $page),
        ]);

        if (!is_array($payload)) {
            return [
                'items' => [],
                'has_next_page' => false,
                'success' => false,
            ];
        }

        $items = [];
        foreach (Arr::get($payload, 'data', []) as $release) {
            $normalized = $this->normalizeRelease($release);
            if ($normalized) {
                $items[] = $normalized;
            }
        }

        return [
            'items' => $items,
            'has_next_page' => (bool) Arr::get($payload, 'meta.pagination.links.next'),
            'success' => true,
        ];
    }

    private function resolveTitle(array $payload): string
    {
        $name = $payload['name'] ?? [];
        if (is_array($name)) {
            foreach (['main', 'english', 'alternative'] as $key) {
                $value = $name[$key] ?? null;
                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }

        if (is_string($payload['title'] ?? null) && $payload['title'] !== '') {
            return $payload['title'];
        }

        return 'Неизвестное аниме';
    }

    private function buildUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(self::BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    private function extractStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#i', $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    private function normalizeRelease(array $release): ?array
    {
        if (empty($release['id'])) {
            return null;
        }

        $title = $this->resolveTitle($release);
        $posterPath = Arr::get($release, 'poster.optimized.preview')
            ?? Arr::get($release, 'poster.optimized.src')
            ?? Arr::get($release, 'poster.preview')
            ?? Arr::get($release, 'poster.src');

        return [
            'id' => (int) $release['id'],
            'title' => $title,
            'poster_url' => $posterPath ? $this->buildUrl($posterPath) : null,
            'type' => Arr::get($release, 'type.description'),
            'year' => Arr::get($release, 'year'),
            'episodes_total' => Arr::get($release, 'episodes_total'),
            'alias' => Arr::get($release, 'alias'),
        ];
    }

    /**
     * @param array<int, mixed> $episodes
     * @return array<int, array<string, mixed>>
     */
    private function normalizeEpisodes(array $episodes): array
    {
        $normalized = [];

        foreach ($episodes as $episode) {
            $numberValue = Arr::get($episode, 'ordinal');
            $episodeNumber = is_numeric($numberValue) ? (int) $numberValue : null;
            if ($episodeNumber === null || $episodeNumber <= 0) {
                continue;
            }

            $titleCandidates = [
                Arr::get($episode, 'name'),
                Arr::get($episode, 'name_english'),
            ];

            $title = null;
            foreach ($titleCandidates as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    $title = trim($candidate);
                    break;
                }
            }

            $streams = [];
            foreach (self::EPISODE_QUALITY_ORDER as $quality) {
                $key = sprintf('hls_%d', $quality);
                $url = Arr::get($episode, $key);
                if (is_string($url) && trim($url) !== '') {
                    $streams[sprintf('%dp', $quality)] = trim($url);
                }
            }

            $defaultQuality = null;
            foreach (self::EPISODE_QUALITY_ORDER as $quality) {
                $label = sprintf('%dp', $quality);
                if (isset($streams[$label])) {
                    $defaultQuality = $label;
                    break;
                }
            }

            $duration = Arr::get($episode, 'duration');
            $durationSeconds = is_numeric($duration) ? (int) $duration : null;

            $normalized[] = [
                'number' => $episodeNumber,
                'title' => $title ?? sprintf('Серия %02d', $episodeNumber),
                'duration_seconds' => $durationSeconds,
                'streams' => $streams,
                'default_quality' => $defaultQuality,
            ];
        }

        usort($normalized, static fn (array $left, array $right) => $left['number'] <=> $right['number']);

        return $normalized;
    }

    /**
     * @param array<int, mixed> $related
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRelated(array $related, int $currentId): array
    {
        $normalized = [];

        foreach ($related as $item) {
            $release = [];

            if (is_array($item)) {
                if (is_array($item['release'] ?? null)) {
                    $release = $item['release'];
                } else {
                    $release = $item;
                }
            }

            if (empty($release)) {
                continue;
            }

            $releaseId = Arr::get($release, 'id');
            if (!is_numeric($releaseId)) {
                continue;
            }

            $releaseId = (int) $releaseId;
            if ($releaseId <= 0 || $releaseId === $currentId) {
                continue;
            }

            $title = $this->resolveTitle($release);
            $posterPath = Arr::get($release, 'poster.optimized.preview')
                ?? Arr::get($release, 'poster.optimized.src')
                ?? Arr::get($release, 'poster.preview')
                ?? Arr::get($release, 'poster.src');

            $identifier = Arr::get($release, 'alias');
            if (!is_string($identifier) || trim($identifier) === '') {
                $identifier = Arr::get($release, 'code');
            }

            if (is_string($identifier)) {
                $identifier = trim($identifier);
            }

            if (!is_string($identifier) || $identifier === '') {
                $identifier = (string) $releaseId;
            }

            $relation = Arr::get($item, 'relation');
            if (is_array($relation)) {
                $relation = Arr::get($relation, 'title')
                    ?? Arr::get($relation, 'name')
                    ?? Arr::get($relation, 'type');
            }

            $normalized[] = [
                'id' => $releaseId,
                'title' => $title,
                'poster_url' => $posterPath ? $this->buildUrl($posterPath) : null,
                'identifier' => $identifier,
                'alias' => Arr::get($release, 'alias'),
                'relation' => is_string($relation) && $relation !== '' ? $relation : null,
            ];
        }

        return $normalized;
    }

    private function fetchFranchiseReleases(int $releaseId): array
    {
        if ($releaseId <= 0) {
            return [];
        }

        $url = sprintf('%s%s/%d', self::API_BASE_URL, self::FRANCHISE_RELEASE_ENDPOINT, $releaseId);
        $payload = $this->makeRequest($url);

        if (!is_array($payload) || empty($payload)) {
            return [];
        }

        return $this->normalizeFranchiseReleases($payload, $releaseId);
    }

    private function normalizeFranchiseReleases(array $franchisePayload, int $currentId): array
    {
        $normalized = [];

        foreach ($franchisePayload as $franchise) {
            if (!is_array($franchise)) {
                continue;
            }

            $franchiseReleases = Arr::get($franchise, 'franchise_releases');
            if (!is_array($franchiseReleases)) {
                continue;
            }

            foreach ($franchiseReleases as $item) {
                $release = Arr::get($item, 'release');
                if (!is_array($release)) {
                    continue;
                }

                $releaseId = Arr::get($release, 'id');
                if (!is_numeric($releaseId)) {
                    continue;
                }

                $releaseId = (int) $releaseId;
                if ($releaseId <= 0 || $releaseId === $currentId) {
                    continue;
                }

                $identifier = Arr::get($release, 'alias');
                if (is_string($identifier)) {
                    $identifier = trim($identifier);
                }

                if (!is_string($identifier) || $identifier === '') {
                    $identifier = Arr::get($release, 'code');
                }

                if (is_string($identifier)) {
                    $identifier = trim($identifier);
                }

                if (!is_string($identifier) || $identifier === '') {
                    $identifier = (string) $releaseId;
                }

                $posterPath = Arr::get($release, 'poster.optimized.preview')
                    ?? Arr::get($release, 'poster.optimized.src')
                    ?? Arr::get($release, 'poster.preview')
                    ?? Arr::get($release, 'poster.src');

                $sortOrder = Arr::get($item, 'sort_order');

                $normalized[] = [
                    'id' => $releaseId,
                    'title' => $this->resolveTitle($release),
                    'poster_url' => $posterPath ? $this->buildUrl($posterPath) : null,
                    'identifier' => $identifier,
                    'alias' => Arr::get($release, 'alias'),
                    'relation' => $this->buildFranchiseRelationLabel($release),
                    'sort_order' => is_numeric($sortOrder)
                        ? (int) $sortOrder
                        : null,
                ];
            }
        }

        usort($normalized, static function (array $left, array $right): int {
            $leftOrder = $left['sort_order'] ?? PHP_INT_MAX;
            $rightOrder = $right['sort_order'] ?? PHP_INT_MAX;

            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strcasecmp($left['title'] ?? '', $right['title'] ?? '');
        });

        foreach ($normalized as &$item) {
            unset($item['sort_order']);
        }
        unset($item);

        return $normalized;
    }

    private function buildFranchiseRelationLabel(array $release): ?string
    {
        $season = Arr::get($release, 'season.description');
        $season = is_string($season) ? trim($season) : '';

        $year = Arr::get($release, 'year');
        $year = is_numeric($year) ? (string) $year : '';

        $parts = array_values(array_filter([$season, $year], static function ($value) {
            return is_string($value) && $value !== '';
        }));

        if (!empty($parts)) {
            return implode(' • ', $parts);
        }

        $type = Arr::get($release, 'type.description');
        if (is_string($type) && trim($type) !== '') {
            return trim($type);
        }

        return null;
    }

    private function makeRequest(string $url, array $query = []): ?array
    {
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $statusCode = $this->extractStatusCode($http_response_header ?? []);
        if ($statusCode < 200 || $statusCode >= 300) {
            return null;
        }

        $payload = json_decode($response, true);
        if (!is_array($payload)) {
            return null;
        }

        return $payload;
    }
}
