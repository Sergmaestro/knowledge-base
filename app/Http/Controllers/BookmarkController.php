<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleBookmarkRequest;
use App\Services\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function __construct(
        private readonly BookmarkService $bookmarkService
    ) {}

    public function toggle(ToggleBookmarkRequest $request): JsonResponse
    {
        return response()->json(
            $this->bookmarkService->toggle(
                $request->question_id,
                $request->user()->id
            )
        );
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->bookmarkService->getUserBookmarks($request->user()->id)
        );
    }
}
