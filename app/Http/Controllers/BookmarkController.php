<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleBookmarkRequest;
use App\Services\BookmarkService;
use App\Services\TopicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookmarkController extends Controller
{
    public function __construct(
        private readonly BookmarkService $bookmarkService,
        private readonly TopicService $topicService,
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

    public function index(Request $request)
    {
        $authUserId = $request->user()->id;

        return Inertia::render('Bookmarks', [
            'bookmarks' => $this->bookmarkService->getUserBookmarks($authUserId),
            'topics' => $this->topicService->getAllWithProgress($authUserId),
        ]);
    }
}
