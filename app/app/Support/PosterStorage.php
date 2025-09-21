<?php

namespace App\Support;

class PosterStorage
{
    private const DIRECTORY = 'data/posters';

    public function store(?string $source, ?string $existingPoster, ?string $existingRemoteUrl, int $animeId): ?string
    {
        $source = $this->sanitize($source);
        if ($source === null) {
            return $existingPoster;
        }

        $localPath = $this->normalizeLocalPath($source);
        if ($localPath !== null) {
            if ($existingPoster && $existingPoster !== $localPath && !$this->fileExists($localPath)) {
                return $existingPoster;
            }

            return $localPath;
        }

        if (!$this->isRemote($source)) {
            return $existingPoster;
        }

        if (
            $existingPoster &&
            $existingRemoteUrl &&
            hash_equals($existingRemoteUrl, $source) &&
            $this->fileExists($existingPoster)
        ) {
            return $existingPoster;
        }

        $data = $this->download($source);
        if ($data === null) {
            return $existingPoster;
        }

        if (!$this->ensureDirectory()) {
            return $existingPoster;
        }

        $relativePath = $this->buildFileName($animeId, $source);
        $fullPath = $this->fullPath($relativePath);

        if (@file_put_contents($fullPath, $data) === false) {
            return $existingPoster;
        }

        if ($existingPoster && $existingPoster !== $relativePath) {
            $this->delete($existingPoster);
        }

        return $relativePath;
    }

    public function resolvePosterUrl(?string $source, ?string $existingRemoteUrl): ?string
    {
        $source = $this->sanitize($source);
        if ($source === null) {
            return $existingRemoteUrl;
        }

        $localPath = $this->normalizeLocalPath($source);
        if ($localPath !== null) {
            return $existingRemoteUrl;
        }

        if ($this->isRemote($source)) {
            return $source;
        }

        return $existingRemoteUrl;
    }

    public function buildPublicUrl(?string $poster): ?string
    {
        $poster = $this->sanitize($poster);
        if ($poster === null) {
            return null;
        }

        $localPath = $this->normalizeLocalPath($poster);
        if ($localPath === null) {
            return null;
        }

        return '/' . ltrim($localPath, '/');
    }

    private function sanitize(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function isRemote(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    private function normalizeLocalPath(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $components = parse_url($value);
        if ($components !== false && isset($components['scheme'], $components['host'])) {
            $path = $components['path'] ?? '';
            if (is_string($path) && $path !== '') {
                $value = $path;
            }
        }

        $value = ltrim($value, '/');

        if (str_starts_with($value, self::DIRECTORY . '/')) {
            return $value;
        }

        return null;
    }

    private function ensureDirectory(): bool
    {
        $directory = $this->fullPath(self::DIRECTORY);
        if (is_dir($directory)) {
            return true;
        }

        return @mkdir($directory, 0775, true) || is_dir($directory);
    }

    private function buildFileName(int $animeId, string $source): string
    {
        $extension = $this->guessExtension($source);
        $hash = substr(sha1($source), 0, 12);

        return sprintf('%s/%d-%s.%s', self::DIRECTORY, $animeId, $hash, $extension);
    }

    private function guessExtension(string $source): string
    {
        $path = parse_url($source, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpeg' => 'jpg',
            'jpg', 'png', 'webp', 'gif' => $extension,
            default => 'jpg',
        };
    }

    private function fullPath(string $relative): string
    {
        return base_path('public/' . ltrim($relative, '/'));
    }

    private function fileExists(string $relative): bool
    {
        return is_file($this->fullPath($relative));
    }

    private function delete(string $relative): void
    {
        $fullPath = $this->fullPath($relative);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function download(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            return null;
        }

        $statusCode = $this->extractStatusCode($http_response_header ?? []);
        if ($statusCode < 200 || $statusCode >= 300) {
            return null;
        }

        return $data;
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
