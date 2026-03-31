<?php

namespace App\Http\Controllers;

use App\Services\BookmarkService;
use App\Services\TopicService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookmarksPageController extends Controller
{
    public function __construct(
        private readonly TopicService $topicService,
        private readonly BookmarkService $bookmarkService,
    ) {}

    public function index(Request $request)
    {
        $authUserId = $request->user()->id;

        return Inertia::render('Bookmarks', [
            'bookmarks' => $this->bookmarkService->getUserBookmarks($authUserId),
            'topics' => $this->topicService->getAllWithProgress($authUserId),
        ]);
    }
}
