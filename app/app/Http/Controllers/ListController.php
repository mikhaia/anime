<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnimeCatalogCache;
use App\Models\Anime;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\WatchProgress;
use App\Support\AnilibriaClient;
use Illuminate\Support\Facades\Auth;

class ListController extends Controller
{
    public function new(Request $request)
    {
        $page = $request->input('page', 1);
        $results = AnimeCatalogCache::query()->where('page', $page)->where('category', 'lite_new')->whereDate('cached_date', date('Y-m-d'))->first();

        if (!$results) {
            $client = new AnilibriaClient();
            $results = $client->fetchLite('lite_new', $page);
            $animeIds = $results['animeIds'];
        } else {
            $animeIds = $results->anime_ids;
        }

        $items = Anime::query()->whereIn('id', $animeIds)->get();

        return view('lite.list', [
            'items' => $items,
            'searchQuery' => null,
        ]);
    }

    public function top(Request $request)
    {
        $page = $request->input('page', 1);
        $results = AnimeCatalogCache::query()->where('page', $page)->where('category', 'lite_top')->whereDate('cached_date', date('Y-m-d'))->first();

        if (!$results) {
            $client = new AnilibriaClient();
            $results = $client->fetchLite('lite_top', $page);
            $animeIds = $results['animeIds'];
        } else {
            $animeIds = $results->anime_ids;
        }

        $items = Anime::query()->whereIn('id', $animeIds)->get();

        return view('lite.list', [
            'items' => $items,
            'searchQuery' => null,
        ]);
    }

    public function search(Request $request)
    {
        $searchQuery = trim((string) $request->input('query', ''));
        $page = $request->input('page', 1);

        if (!$searchQuery) {
            $genres = Genre::all();

            $watchProgress = collect();
            $favorites = collect();
            $currentUser = Auth::user();

            if ($currentUser) {
                $watchProgress = WatchProgress::where('user_id', $currentUser->id)
                    ->with('anime')
                    ->latest('updated_at')
                    ->take(5)
                    ->get();

                $favorites = Favorite::where('user_id', $currentUser->id)
                    ->with('anime')
                    ->latest('created_at')
                    ->take(5)
                    ->get();
            }

            return view('lite.search', [
                'items' => [],
                'genres' => $genres,
                'searchQuery' => $searchQuery,
                'watchProgress' => $watchProgress,
                'favorites' => $favorites,
            ]);
        }

        $client = new AnilibriaClient();
        $cacheCategory = 'lite_search::' . $searchQuery;

        $response = $client->fetchLite($cacheCategory, $page, 24, $this->latinToCyr($searchQuery));
        $animeIds = $response['animeIds'] ?? [];

        $items = Anime::query()->whereIn('id', $animeIds)->paginate(24);

        return view('lite.list', [
            'items' => $items,
            'page' => $page,
            'searchQuery' => $searchQuery,
        ]);
    }

    public function genre(Request $request)
    {
        $genreId = $request->route('genre');
        $page = $request->input('page', 1);

        $client = new AnilibriaClient();
        $cacheCategory = 'lite_genre::' . $genreId;

        $response = $client->fetchLite($cacheCategory, $page, 24, '', [$genreId]);
        $animeIds = $response['animeIds'] ?? [];

        $items = Anime::query()->whereIn('id', $animeIds)->limit(24)->get();

        return view('lite.list', [
            'items' => $items,
            'searchQuery' => null,
        ]);
    }

    public function fav(Request $request)
    {

        $animeIds = Favorite::where('user_id', Auth::id())->pluck('anime_id');
        $items = Anime::query()->whereIn('id', $animeIds)->paginate(24);
        return view('lite.list', [
            'items' => $items,
        ]);
    }

    private function latinToCyr($s)
    {
        $map = [
            'a' => 'а',
            'b' => 'б',
            'v' => 'в',
            'g' => 'г',
            'd' => 'д',
            'e' => 'е',
            'yo' => 'ё',
            'zh' => 'ж',
            'z' => 'з',
            'i' => 'и',
            'j' => 'й',
            'k' => 'к',
            'l' => 'л',
            'm' => 'м',
            'n' => 'н',
            'o' => 'о',
            'p' => 'п',
            'r' => 'р',
            's' => 'с',
            't' => 'т',
            'u' => 'у',
            'f' => 'ф',
            'h' => 'х',
            'ch' => 'ч',
            'sh' => 'ш',
            'yu' => 'ю',
            'ya' => 'я',
        ];

        foreach (['yo', 'zh', 'ch', 'sh', 'yu', 'ya'] as $dbl) {
            $s = str_ireplace($dbl, $map[$dbl], $s);
        }

        $s = str_ireplace(array_keys($map), array_values($map), $s);

        return $s;
    }
}
