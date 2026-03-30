<?php

namespace App\Repositories;

use App\Models\Question;
use Illuminate\Support\Collection;

class QuestionRepository
{
    public function findBySlug(string $slug): Question
    {
        return Question::with('topic')->where('slug', $slug)->firstOrFail();
    }

    public function search(string $query): Collection
    {
        return Question::search($query)->get();
    }

    public function getNeighbors(Question $question): array
    {
        $next = Question::select('slug', 'title')
            ->where('topic_id', $question->topic_id)
            ->where('order_index', '>', $question->order_index)
            ->orderBy('order_index')
            ->first();

        $prev = Question::select('slug', 'title')
            ->where('topic_id', $question->topic_id)
            ->where('order_index', '<', $question->order_index)
            ->orderBy('order_index', 'desc')
            ->first();

        return [
            'next' => $next?->toArray(),
            'prev' => $prev?->toArray(),
        ];
    }

    public function upsert(array $data): void
    {
        Question::upsert(
            $data,
            ['slug'],
            ['topic_id', 'title', 'content', 'order_index', 'updated_at']
        );
    }

    public function getBookmarkedQuestions(int $userId): Collection
    {
        return Question::select([
            'questions.id', 'questions.title', 'questions.slug', 'questions.topic_id'
        ])
            ->with(['topic:id,name'])
            ->join('bookmarks', 'bookmarks.question_id', '=', 'questions.id')
            ->where('bookmarks.user_id', $userId)
            ->get();
    }
}
