<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnimeCatalogCache;
use App\Models\Anime;
use App\Support\AnilibriaClient;

class ListController extends Controller
{
    public function new(Request $request)
    {

        $page = $request->input('page', 1);
        $results = AnimeCatalogCache::query()
            ->where('page', $page)
            ->where('category', 'lite_new')
            ->whereDate('cached_date', date('Y-m-d'))
            ->first();

        if (!$results) {
            $client = new AnilibriaClient();
            $results = $client->fetchLite('lite_new', $page);
            $animeIds = $results['animeIds'];
        } else {
            $animeIds = $results->anime_ids;
        }

        $items = Anime::query()
            ->whereIn('id', $animeIds ?? [])
            ->paginate(24);

        return view('lite.list', [
            'items' => $items,
            'page' => $page,
        ]);
    }

    public function top(Request $request)
    {

        $page = $request->input('page', 1);
        $results = AnimeCatalogCache::query()
            ->where('page', $page)
            ->where('category', 'lite_top')
            ->whereDate('cached_date', date('Y-m-d'))
            ->first();

        if (!$results) {
            $client = new AnilibriaClient();
            $results = $client->fetchLite('lite_top', $page);
            $animeIds = $results['animeIds'];
        } else {
            $animeIds = $results->anime_ids;
        }

        $items = Anime::query()
            ->whereIn('id', $animeIds ?? [])
            ->paginate(24);

        return view('lite.list', [
            'items' => $items,
            'page' => $page,
        ]);
    }
}
