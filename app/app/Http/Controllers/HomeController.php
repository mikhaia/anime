<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): string
    {
        $searchQuery = trim((string) $request->input('search', ''));

        return view('home', [
            'searchQuery' => $searchQuery,
        ])->render();
    }
}
