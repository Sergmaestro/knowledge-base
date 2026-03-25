<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Repositories\TopicRepository;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function __construct(
        private TopicRepository $topicRepository
    ) {}

    public function show(string $slug)
    {
        $question = Question::where('slug', $slug)
            ->with('topic')
            ->firstOrFail();

        $isCompleted = false;
        $isBookmarked = false;

        if (auth()->check()) {
            $progress = auth()->user()->progress()
                ->where('question_id', $question->id)
                ->first();
            $isCompleted = $progress?->completed ?? false;

            $isBookmarked = auth()->user()->bookmarks()
                ->where('question_id', $question->id)
                ->exists();
        }

        $nextQuestion = Question::where('topic_id', $question->topic_id)
            ->where('order_index', '>', $question->order_index)
            ->orderBy('order_index')
            ->first();

        $prevQuestion = Question::where('topic_id', $question->topic_id)
            ->where('order_index', '<', $question->order_index)
            ->orderBy('order_index', 'desc')
            ->first();

        return Inertia::render('Question', [
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'content' => $question->content,
                'slug' => $question->slug,
                'topic' => [
                    'name' => $question->topic->name,
                    'slug' => $question->topic->slug,
                ],
                'is_completed' => $isCompleted,
                'is_bookmarked' => $isBookmarked,
            ],
            'next_question' => $nextQuestion ? [
                'slug' => $nextQuestion->slug,
                'title' => $nextQuestion->title,
            ] : null,
            'prev_question' => $prevQuestion ? [
                'slug' => $prevQuestion->slug,
                'title' => $prevQuestion->title,
            ] : null,
            'topics' => $this->topicRepository->getAllWithProgress(),
        ]);
    }
}
