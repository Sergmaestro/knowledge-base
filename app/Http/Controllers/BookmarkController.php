<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleBookmarkRequest;
use App\Repositories\BookmarkRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function __construct(
        private readonly BookmarkRepository $bookmarkRepository
    ) {}

    public function toggle(ToggleBookmarkRequest $request): JsonResponse
    {
        $isBookmarked = $this->bookmarkRepository->toggle(
            $request->question_id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'bookmarked' => $isBookmarked,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->bookmarkRepository->getUserBookmarks($request->user()->id)
        );
    }
}
