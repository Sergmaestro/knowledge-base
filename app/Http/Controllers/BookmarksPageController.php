<?php

namespace App\Http\Controllers;

use App\Repositories\QuestionRepository;
use App\Repositories\TopicRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookmarksPageController extends Controller
{
    public function __construct(
        private readonly TopicRepository $topicRepository,
        private readonly QuestionRepository $questionRepository,
    )
    {
    }

    public function index(Request $request)
    {
        return Inertia::render('Bookmarks', [
            'bookmarks' => $this->questionRepository->getBookmarkedQuestions($request->user()->id),
            'topics' => $this->topicRepository->getAllWithProgress(),
        ]);
    }
}
