<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Inertia\Inertia;

class TopicController extends Controller
{
    public function show(string $slug)
    {
        $topic = Topic::where('slug', $slug)
            ->with(['questions' => function ($query) {
                $query->select('id', 'topic_id', 'title', 'slug', 'order_index');
            }])
            ->firstOrFail();

        $questions = $topic->questions->map(function ($question) {
            $question->is_completed = false;
            if (auth()->check()) {
                $progress = auth()->user()->progress()
                    ->where('question_id', $question->id)
                    ->first();
                $question->is_completed = $progress?->completed ?? false;
            }

            return $question;
        });

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

        return Inertia::render('Topic', [
            'topic' => [
                'id' => $topic->id,
                'name' => $topic->name,
                'slug' => $topic->slug,
                'description' => $topic->description,
                'icon' => $topic->icon,
            ],
            'questions' => $questions,
            'progress' => auth()->check()
                ? [
                    'completed' => $questions->where('is_completed', true)->count(),
                    'total' => $questions->count(),
                ]
                : null,
            'topics' => $topics,
        ]);
    }
}
