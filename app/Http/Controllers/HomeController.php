<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Question;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $topics = Topic::withCount('questions')
            ->orderBy('order_index')
            ->get()
            ->map(function ($topic) {
                $topic->progress = null;
                if (auth()->check()) {
                    $completedCount = auth()->user()->progress()
                        ->whereHas('question', fn($q) => $q->where('topic_id', $topic->id))
                        ->where('completed', true)
                        ->count();
                    $topic->progress = [
                        'completed' => $completedCount,
                        'total' => $topic->questions_count,
                    ];
                }
                return $topic;
            });

        return Inertia::render('Home', [
            'topics' => $topics,
        ]);
    }
}
