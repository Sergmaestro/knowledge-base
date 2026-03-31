<?php

namespace App\Http\Controllers;

use App\Repositories\QuestionRepository;
use App\Services\TopicService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookmarksPageController extends Controller
{
    public function __construct(
        private readonly TopicService $topicService,
        private readonly QuestionRepository $questionRepository,
    )
    {
    }

    public function index(Request $request)
    {
        $authUserId = $request->user()->id;

        return Inertia::render('Bookmarks', [
            'bookmarks' => $this->questionRepository->getBookmarkedQuestions($authUserId),
            'topics' => $this->topicService->getAllWithProgress($authUserId),
        ]);
    }
}
