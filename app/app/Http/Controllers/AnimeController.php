<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnimeController extends Controller
{
    public function __construct(private readonly \App\Support\AnimeCatalogService $catalogService)
    {
    }

    public function catalog(Request $request, string $category): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->catalogService->getCatalogPage($category, $page);
        if (!$result['valid']) {
            return response()->json([
                'message' => 'Категория не найдена.',
            ], 404);
        }

        if ($result['failed'] && $result['items']->isEmpty()) {
            return response()->json([
                'message' => 'Не удалось получить данные от сервера аниме.',
            ], 502);
        }

        $items = $result['items']
            ->map(fn (Anime $anime) => $this->catalogService->formatAnime($anime))
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $result['page'],
                'has_next_page' => (bool) $result['has_next_page'],
                'cached' => (bool) $result['cached'],
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

        $result = $this->catalogService->searchLocal($query, $page);

        $items = collect($result->items())
            ->filter()
            ->map(fn ($item) => $item instanceof Anime ? $item : Anime::query()->find($item['id'] ?? null))
            ->filter()
            ->map(fn (Anime $anime) => $this->catalogService->formatAnime($anime))
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $result->currentPage(),
                'has_next_page' => $result->hasMorePages(),
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

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
