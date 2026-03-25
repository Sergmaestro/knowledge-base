<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\UserProgress;
use App\Repositories\TopicRepository;
use Inertia\Inertia;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicRepository $topicRepository
    ) {}

    public function show(string $slug)
    {
        $topic = Topic::where('slug', $slug)
            ->with(['questions' => function ($query) {
                $query->select('id', 'topic_id', 'title', 'slug', 'order_index');
            }])
            ->firstOrFail();

        $progressByQuestion = [];
        if (auth()->check()) {
            $progressByQuestion = UserProgress::query()
                ->where('user_id', auth()->id())
                ->whereIn('question_id', $topic->questions->pluck('id'))
                ->pluck('completed', 'question_id')
                ->toArray();
        }

        $questions = $topic->questions->map(function ($question) use ($progressByQuestion) {
            $question->is_completed = $progressByQuestion[$question->id] ?? false;

            return $question;
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
            'topics' => $this->topicRepository->getAllWithProgress(),
        ]);
    }
}
