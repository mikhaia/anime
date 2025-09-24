<?php

namespace App\Http\Controllers;

use App\Support\AnimeCatalogService;
use App\Support\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private readonly AnimeCatalogService $catalogService)
    {
    }

    public function index(Request $request): string
    {
        $searchQuery = trim((string) $request->input('search', ''));
        $recentWatchProgress = collect();
        $recentFavorites = collect();

        $user = Auth::user();
        if ($user) {
            $recentWatchProgress = $user->watchProgress()
                ->with('anime')
                ->orderByDesc('updated_at')
                ->limit(3)
                ->get()
                ->filter(static fn ($progress) => $progress->anime !== null)
                ->values();

            $recentFavorites = $user->favorites()
                ->with('anime')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get()
                ->filter(static fn ($favorite) => $favorite->anime !== null)
                ->values();
        }

        return view('home', [
            'searchQuery' => $searchQuery,
            'recentWatchProgress' => $recentWatchProgress,
            'recentFavorites' => $recentFavorites,
        ])->render();
    }

    public function list(Request $request): string
    {
        $mode = $request->input('mode', 'favorites');
        $mode = in_array($mode, ['favorites', 'top', 'new', 'search'], true) ? $mode : 'favorites';
        $page = max(1, (int) $request->input('page', 1));
        $searchQuery = trim((string) $request->input('search', ''));

        $favorites = collect();
        $catalogPaginator = null;
        $catalogMessage = null;
        $searchPaginator = null;

        if ($mode === 'favorites') {
            $user = Auth::user();
            if ($user) {
                $favorites = $user->favorites()
                    ->with('anime')
                    ->orderByDesc('created_at')
                    ->get();
            }
        }

        if (in_array($mode, ['top', 'new'], true)) {
            $result = $this->catalogService->getCatalogPage($mode, $page);

            if (!$result['valid']) {
                abort(404);
            }

            if ($result['failed']) {
                $catalogMessage = $result['items']->isEmpty()
                    ? 'Не удалось загрузить список. Попробуйте обновить страницу позже.'
                    : 'Не удалось обновить данные, показаны сохранённые результаты.';
            }

            $catalogPaginator = $this->catalogService->buildCatalogPaginator($result, $request);
        }

        if ($mode === 'search' && $searchQuery !== '') {
            $searchPaginator = $this->catalogService->searchLocal($searchQuery, $page);
            $searchPaginator->withPath(url('/list'));
            $searchPaginator->appends([
                'mode' => 'search',
                'search' => $searchQuery,
            ]);
        }

        return view('list', [
            'mode' => $mode,
            'favorites' => $favorites,
            'catalogPaginator' => $catalogPaginator,
            'catalogMessage' => $catalogMessage,
            'searchPaginator' => $searchPaginator,
            'searchQuery' => $searchQuery,
        ])->render();
    }
}
