<?php

namespace App\Http\Controllers;

use App\Repositories\SearchRepository;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService
    )
    {
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $results = $this->searchService->search($query);

        $json = json_encode([
            'results' => $results->toArray(),
        ], JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE);

        return response($json, 200, ['Content-Type' => 'application/json']);
    }
}
