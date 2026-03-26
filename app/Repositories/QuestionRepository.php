<?php

namespace App\Repositories;

use App\Models\Question;

class QuestionRepository
{
    public function findBySlug(string $slug): Question
    {
        return Question::where('slug', $slug)
            ->with('topic')
            ->firstOrFail();
    }

    public function getAdjacentQuestions(Question $question): array
    {
        $next = Question::where('topic_id', $question->topic_id)
            ->where('order_index', '>', $question->order_index)
            ->orderBy('order_index')
            ->first();

        $prev = Question::where('topic_id', $question->topic_id)
            ->where('order_index', '<', $question->order_index)
            ->orderBy('order_index', 'desc')
            ->first();

        return [
            'next' => $next ? ['slug' => $next->slug, 'title' => $next->title] : null,
            'prev' => $prev ? ['slug' => $prev->slug, 'title' => $prev->title] : null,
        ];
    }

    public function getUserProgressData(Question $question): array
    {
        if (! auth()->check()) {
            return [
                'is_completed' => false,
                'is_bookmarked' => false,
                'notes' => [],
            ];
        }

        $progress = auth()->user()->progress()
            ->where('question_id', $question->id)
            ->first();

        $isBookmarked = auth()->user()->bookmarks()
            ->where('question_id', $question->id)
            ->exists();

        $notes = auth()->user()->notes()
            ->where('question_id', $question->id)
            ->get()
            ->map(fn ($note) => [
                'id' => $note->id,
                'note' => $note->note,
                'created_at' => $note->created_at->toIsoString(),
            ]);

        return [
            'is_completed' => $progress?->completed ?? false,
            'is_bookmarked' => $isBookmarked,
            'notes' => $notes,
        ];
    }

    public function toResource(Question $question, array $userData, array $adjacent): array
    {
        return [
            'id' => $question->id,
            'title' => $question->title,
            'content' => $question->content,
            'slug' => $question->slug,
            'topic' => [
                'name' => $question->topic->name,
                'slug' => $question->topic->slug,
            ],
            'is_completed' => $userData['is_completed'],
            'is_bookmarked' => $userData['is_bookmarked'],
            'notes' => $userData['notes'],
        ];
    }

    public function upsert(array $data): void
    {
        Question::upsert($data, ['slug'], ['topic_id', 'title', 'content', 'order_index', 'updated_at']);
    }
}
