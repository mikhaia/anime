<?php

namespace App\Http\Controllers;

use App\Support\AnimeCatalogService;
use App\Support\Auth;
use Illuminate\Http\Request;
use App\Models\AnimeCatalogCache;
use App\Models\Anime;

class ListController extends Controller
{
    public function new(Request $request)
    {

        $page = $request->input('page', 1);
        $results = AnimeCatalogCache::query()
            ->where('page', $page)
            ->where('category', 'new')
            ->first();

        $items = Anime::query()
            ->whereIn('id', $results->anime_ids ?? [])
            ->paginate(24);

        return view('lite.list', [
            'items' => $items,
            'mode' => 'new',
            'page' => $page,
        ]);
    }

    public function top(Request $request)
    {

        $page = $request->input('page', 1);
        $results = AnimeCatalogCache::query()
            ->where('page', $page)
            ->where('category', 'top')
            ->first();

        $items = Anime::query()
            ->whereIn('id', $results->anime_ids ?? [])
            ->paginate(24);

        return view('lite.list', [
            'items' => $items,
            'mode' => 'top',
            'page' => $page,
        ]);
    }
}
