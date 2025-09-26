<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\AnimeReleaseCache;
use App\Models\WatchProgress;
use App\Support\AnilibriaClient;
use App\Support\Auth;
use App\Support\PosterStorage;

class WatchController extends Controller
{
    public function show(string $identifier): string
    {
        $query = Anime::query();

        if (ctype_digit($identifier)) {
            $anime = $query->find((int) $identifier);
        } else {
            $anime = $query->where('alias', $identifier)->first();
        }

        /** @var AnilibriaClient $client */
        $client = app(AnilibriaClient::class);

        $cache = $anime ? AnimeReleaseCache::query()->find($anime->getKey()) : null;

        $shouldFetchRelease = !$anime || !$this->hasValidCachedEpisodes($cache);

        if ($shouldFetchRelease) {
            $release = $this->fetchReleaseForAnime($client, $identifier, $anime);

            if ($release) {
                $anime = $this->persistAnimeRelease($release);
                $cache = $this->persistReleaseCache($anime, $release);
            }
        }

        if (!$anime) {
            abort(404);
        }

        $episodes = $this->prepareEpisodesForView($cache?->episodes ?? []);

        if (empty($episodes)) {
            $episodesTotal = (int) ($anime->episodes_total ?? 0);
            $episodesCount = max(1, min($episodesTotal > 0 ? $episodesTotal : 12, 12));

            $baseStream = 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8';

            for ($episodeNumber = 1; $episodeNumber <= $episodesCount; $episodeNumber++) {
                $title = sprintf('Серия %02d', $episodeNumber);

                $episodes[] = [
                    'number' => $episodeNumber,
                    'title' => $title,
                    'description' => '',
                    'duration' => '24 мин.',
                    'stream_url' => $baseStream,
                    'streams' => [
                        '720p' => $baseStream,
                    ],
                    'default_quality' => '720p',
                ];
            }
        }

        $activeEpisode = $episodes[0] ?? null;

        $user = Auth::user();
        if ($user) {
            $progress = WatchProgress::query()
                ->where('user_id', $user->getKey())
                ->where('anime_id', $anime->getKey())
                ->first();

            if ($progress) {
                $progressEpisode = collect($episodes)->firstWhere('number', (int) $progress->episode_number);
                if ($progressEpisode) {
                    $activeEpisode = $progressEpisode;
                }
            }
        }

        $seasons = $this->prepareSeasonsForView($anime, $cache?->related ?? []);

        return view('watch', [
            'anime' => $anime,
            'episodes' => $episodes,
            'activeEpisode' => $activeEpisode,
            'seasons' => $seasons,
        ])->render();
    }

    private function fetchReleaseForAnime(AnilibriaClient $client, string $requestedIdentifier, ?Anime $anime): ?array
    {
        foreach ($this->buildReleaseIdentifierCandidates($requestedIdentifier, $anime) as $candidate) {
            $release = $client->fetchRelease($candidate, true);
            if ($release) {
                return $release;
            }
        }

        return null;
    }

    private function persistReleaseCache(Anime $anime, array $release): AnimeReleaseCache
    {
        $cache = AnimeReleaseCache::query()->firstOrNew(['anime_id' => $anime->getKey()]);

        $cache->anime_id = (int) $anime->getKey();
        $cache->episodes = $this->normalizeEpisodesForStorage($release['episodes'] ?? []);
        $cache->related = $this->normalizeRelatedForStorage($release['related'] ?? []);
        $cache->save();

        return $cache;
    }

    private function hasValidCachedEpisodes(?AnimeReleaseCache $cache): bool
    {
        if (!$cache) {
            return false;
        }

        $episodes = $cache->episodes ?? [];
        if (!is_array($episodes) || empty($episodes)) {
            return false;
        }

        foreach ($episodes as $episode) {
            if (!is_array($episode)) {
                continue;
            }

            if (!empty($episode['streams']) && is_array($episode['streams'])) {
                foreach ($episode['streams'] as $quality => $url) {
                    if (is_string($quality) && is_string($url) && $quality !== '' && $url !== '') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function buildReleaseIdentifierCandidates(string $requestedIdentifier, ?Anime $anime): array
    {
        $candidates = [];

        if ($anime) {
            $alias = is_string($anime->alias ?? null) ? trim($anime->alias) : '';
            if ($alias !== '') {
                $candidates[] = $alias;
            }

            $id = (int) $anime->getKey();
            if ($id > 0) {
                $candidates[] = (string) $id;
            }
        }

        $requested = trim($requestedIdentifier);
        if ($requested !== '') {
            $candidates[] = $requested;
        }

        $uniqueCandidates = [];
        foreach ($candidates as $candidate) {
            if (!in_array($candidate, $uniqueCandidates, true)) {
                $uniqueCandidates[] = $candidate;
            }
        }

        return $uniqueCandidates;
    }

    private function normalizeEpisodesForStorage(array $episodes): array
    {
        $normalized = [];

        foreach ($episodes as $episode) {
            if (!is_array($episode)) {
                continue;
            }

            $number = isset($episode['number']) ? (int) $episode['number'] : null;
            if ($number === null || $number <= 0) {
                continue;
            }

            $streams = [];
            if (!empty($episode['streams']) && is_array($episode['streams'])) {
                foreach ($episode['streams'] as $quality => $url) {
                    if (!is_string($quality) || !is_string($url)) {
                        continue;
                    }

                    $qualityLabel = trim($quality);
                    $streamUrl = trim($url);
                    if ($qualityLabel === '' || $streamUrl === '') {
                        continue;
                    }

                    $streams[$qualityLabel] = $streamUrl;
                }
            }

            if (empty($streams)) {
                continue;
            }

            $defaultQuality = $episode['default_quality'] ?? null;
            if (!is_string($defaultQuality) || !isset($streams[$defaultQuality])) {
                $defaultQuality = array_key_first($streams);
            }

            if (!is_string($defaultQuality) || $defaultQuality === '') {
                $defaultQuality = array_key_first($streams);
            }

            $durationSeconds = null;
            if (isset($episode['duration_seconds']) && is_numeric($episode['duration_seconds'])) {
                $durationSeconds = (int) $episode['duration_seconds'];
            }

            $normalized[] = [
                'number' => $number,
                'title' => is_string($episode['title'] ?? null) ? trim((string) $episode['title']) : sprintf('Серия %02d', $number),
                'description' => is_string($episode['description'] ?? null) ? trim((string) $episode['description']) : '',
                'duration_seconds' => $durationSeconds,
                'streams' => $streams,
                'default_quality' => $defaultQuality,
            ];
        }

        usort($normalized, static fn (array $left, array $right) => $left['number'] <=> $right['number']);

        return array_values($normalized);
    }

    private function normalizeRelatedForStorage(array $related): array
    {
        $normalized = [];

        foreach ($related as $item) {
            if (!is_array($item)) {
                continue;
            }

            $releaseId = isset($item['id']) ? (int) $item['id'] : null;
            if ($releaseId === null || $releaseId <= 0) {
                continue;
            }

            $title = is_string($item['title'] ?? null) && trim($item['title']) !== ''
                ? trim($item['title'])
                : 'Сезон';

            $titleEnglish = is_string($item['title_english'] ?? null) && trim($item['title_english']) !== ''
                ? trim($item['title_english'])
                : null;

            $posterUrl = is_string($item['poster_url'] ?? null) && trim($item['poster_url']) !== ''
                ? trim($item['poster_url'])
                : null;

            $identifier = $item['identifier'] ?? ($item['alias'] ?? null);
            if (is_string($identifier)) {
                $identifier = trim($identifier);
            }

            if (!is_string($identifier) || $identifier === '') {
                $identifier = (string) $releaseId;
            }

            $relation = is_string($item['relation'] ?? null) && trim($item['relation']) !== ''
                ? trim($item['relation'])
                : null;

            $normalized[] = [
                'id' => $releaseId,
                'title' => $title,
                'title_english' => $titleEnglish,
                'poster_url' => $posterUrl,
                'identifier' => $identifier,
                'alias' => is_string($item['alias'] ?? null) ? trim((string) $item['alias']) : null,
                'relation' => $relation,
            ];
        }

        return array_values($normalized);
    }

    private function prepareEpisodesForView(array $episodes): array
    {
        $prepared = [];

        foreach ($episodes as $episode) {
            if (!is_array($episode)) {
                continue;
            }

            $number = isset($episode['number']) ? (int) $episode['number'] : null;
            if ($number === null || $number <= 0) {
                continue;
            }

            $streams = $this->normalizeStreamsForView($episode['streams'] ?? []);
            if (empty($streams)) {
                continue;
            }

            $defaultQuality = $episode['default_quality'] ?? null;
            if (!is_string($defaultQuality) || !isset($streams[$defaultQuality])) {
                $defaultQuality = array_key_first($streams);
            }

            if ($defaultQuality === null) {
                continue;
            }

            $durationSeconds = null;
            if (isset($episode['duration_seconds']) && is_numeric($episode['duration_seconds'])) {
                $durationSeconds = (int) $episode['duration_seconds'];
            }

            $title = is_string($episode['title'] ?? null) && trim($episode['title']) !== ''
                ? trim($episode['title'])
                : sprintf('Серия %02d', $number);

            $prepared[] = [
                'number' => $number,
                'title' => $title,
                'description' => is_string($episode['description'] ?? null) ? trim((string) $episode['description']) : '',
                'duration' => $this->formatEpisodeDuration($durationSeconds),
                'stream_url' => $streams[$defaultQuality],
                'streams' => $streams,
                'default_quality' => $defaultQuality,
            ];
        }

        usort($prepared, static fn (array $left, array $right) => $left['number'] <=> $right['number']);

        return array_values($prepared);
    }

    private function normalizeStreamsForView($streams): array
    {
        if (!is_array($streams)) {
            return [];
        }

        $entries = [];
        foreach ($streams as $quality => $url) {
            if (!is_string($quality) || !is_string($url)) {
                continue;
            }

            $qualityLabel = trim($quality);
            $streamUrl = trim($url);

            if ($qualityLabel === '' || $streamUrl === '') {
                continue;
            }

            $entries[] = [$qualityLabel, $streamUrl];
        }

        if (empty($entries)) {
            return [];
        }

        usort($entries, static function (array $left, array $right): int {
            $parseQuality = static function (string $value): int {
                if (preg_match('/(\d+)/', $value, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            };

            $leftQuality = $parseQuality($left[0]);
            $rightQuality = $parseQuality($right[0]);

            if ($leftQuality === $rightQuality) {
                return strnatcasecmp($right[0], $left[0]);
            }

            return $rightQuality <=> $leftQuality;
        });

        $normalized = [];
        foreach ($entries as [$quality, $url]) {
            $normalized[$quality] = $url;
        }

        return $normalized;
    }

    private function prepareSeasonsForView(Anime $anime, array $related): array
    {
        $currentReleaseId = (int) $anime->getKey();
        $potentialIdentifiers = array_values(array_filter([
            is_string($anime->alias ?? null) && trim($anime->alias) !== '' ? trim($anime->alias) : null,
            $currentReleaseId > 0 ? (string) $currentReleaseId : null,
        ], static fn ($value) => is_string($value) && $value !== ''));

        $seasonsById = [];

        foreach ($related as $relatedSeason) {
            if (!is_array($relatedSeason)) {
                continue;
            }

            $releaseId = isset($relatedSeason['id']) ? (int) $relatedSeason['id'] : null;
            if (!$releaseId || $releaseId <= 0 || $releaseId === $currentReleaseId) {
                continue;
            }

            $identifier = $relatedSeason['identifier'] ?? ($relatedSeason['alias'] ?? null);
            if (is_string($identifier)) {
                $identifier = trim($identifier);
            }

            if (!is_string($identifier) || $identifier === '') {
                $identifier = (string) $releaseId;
            }

            $title = is_string($relatedSeason['title'] ?? null) && trim($relatedSeason['title']) !== ''
                ? trim($relatedSeason['title'])
                : 'Сезон';

            $relation = is_string($relatedSeason['relation'] ?? null) && trim($relatedSeason['relation']) !== ''
                ? trim($relatedSeason['relation'])
                : null;

            $isActive = in_array($identifier, $potentialIdentifiers, true)
                || (string) $releaseId === ($potentialIdentifiers[1] ?? null);

            $seasonsById[$releaseId] = [
                'title' => $title,
                'identifier' => $identifier,
                'relation' => $relation,
                'is_active' => $isActive,
            ];
        }

        $seasons = array_values($seasonsById);

        $hasActiveSeason = false;
        foreach ($seasons as &$season) {
            if (!empty($season['is_active'])) {
                $hasActiveSeason = true;
                $season['is_active'] = true;
                $season['title'] = is_string($anime->title ?? null) && trim($anime->title) !== ''
                    ? $anime->title
                    : $season['title'];
                if (!empty($potentialIdentifiers)) {
                    $season['identifier'] = $potentialIdentifiers[0];
                }
                if (!is_string($season['relation'] ?? null) || trim((string) $season['relation']) === '') {
                    $season['relation'] = 'Текущий сезон';
                }
                break;
            }
        }
        unset($season);

        if (!$hasActiveSeason) {
            $fallbackIdentifier = $potentialIdentifiers[0]
                ?? ($potentialIdentifiers[1] ?? ($currentReleaseId > 0 ? (string) $currentReleaseId : null));

            if (is_string($fallbackIdentifier) && $fallbackIdentifier !== '') {
                array_unshift($seasons, [
                    'title' => is_string($anime->title ?? null) && trim($anime->title) !== ''
                        ? $anime->title
                        : 'Текущий сезон',
                    'identifier' => $fallbackIdentifier,
                    'relation' => 'Текущий сезон',
                    'is_active' => true,
                ]);
            }
        }

        return $seasons;
    }

    private function formatEpisodeDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '—';
        }

        $minutes = (int) round($seconds / 60);
        $minutes = max($minutes, 1);

        return sprintf('%d мин.', $minutes);
    }

    private function persistAnimeRelease(array $release): Anime
    {
        $id = (int) ($release['id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('Release identifier must be a positive integer.');
        }

        $existing = Anime::query()->find($id);

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
                'title_english' => $this->normalizeEnglishTitle($release['title_english'] ?? null),
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
}
