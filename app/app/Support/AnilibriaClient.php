<?php

namespace App\Support;

use Illuminate\Support\Arr;

class AnilibriaClient
{
    private const API_BASE_URL = 'https://anilibria.top/api/v1/anime';
    private const BASE_URL = 'https://anilibria.top';

    public function fetchRelease(string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $url = sprintf('%s/releases/%s', self::API_BASE_URL, rawurlencode($identifier));
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
        if (!is_array($payload) || empty($payload['id'])) {
            return null;
        }

        $title = $this->resolveTitle($payload);
        $posterPath = Arr::get($payload, 'poster.optimized.preview')
            ?? Arr::get($payload, 'poster.optimized.src')
            ?? Arr::get($payload, 'poster.preview')
            ?? Arr::get($payload, 'poster.src');
        $posterUrl = $posterPath ? $this->buildUrl($posterPath) : null;

        return [
            'id' => (int) $payload['id'],
            'title' => $title,
            'poster_url' => $posterUrl,
            'type' => Arr::get($payload, 'type.description'),
            'year' => Arr::get($payload, 'year'),
            'episodes_total' => Arr::get($payload, 'episodes_total'),
            'alias' => Arr::get($payload, 'alias'),
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
}
