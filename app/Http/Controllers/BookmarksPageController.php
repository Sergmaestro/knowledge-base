<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Inertia\Inertia;

class BookmarksPageController extends Controller
{
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

        $topics = Topic::withCount('questions')
            ->orderBy('order_index')
            ->get()
            ->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'slug' => $topic->slug,
                    'questions_count' => $topic->questions_count,
                ];
            });

        return Inertia::render('Bookmarks', [
            'bookmarks' => $bookmarks,
            'topics' => $topics,
        ]);
    }
}
