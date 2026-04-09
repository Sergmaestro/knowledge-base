<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\SearchService;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService
    ) {}

    public function search(SearchRequest $request)
    {
        return response()->json([
            'results' => $this->searchService->search($request->q),
        ]);
    }
}
