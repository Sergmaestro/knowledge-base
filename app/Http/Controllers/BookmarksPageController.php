<?php

namespace App\Http\Controllers;

use App\Repositories\TopicRepository;
use Inertia\Inertia;

class BookmarksPageController extends Controller
{
    public function __construct(
        private readonly TopicRepository $topicRepository
    ) {}

    public function index()
    {
        $bookmarks = auth()->user()
            ->bookmarks()
            ->with(['question' => function ($query) {
                $query->with('topic:id,name,slug');
            }])
            ->get()
            ->pluck('question')
            ->filter();

        return Inertia::render('Bookmarks', [
            'bookmarks' => $bookmarks,
            'topics' => $this->topicRepository->getAllWithProgress(),
        ]);
    }
}
