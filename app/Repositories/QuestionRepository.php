<?php

namespace App\Repositories;

use App\Models\Question;
use DB;
use Illuminate\Support\Collection;

class QuestionRepository
{
    public function findBySlug(string $slug): Question
    {
        return Question::with('topic')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function search(string $query): Collection
    {
        $query = trim($query);
        $queryLength = mb_strlen($query);
        $lowerQuery = mb_strtolower($query);

        return Question::when(
            $queryLength < 4 || !$this->supportsFulltext(),
            callback: fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'LIKE', "%$lowerQuery%")
                ->orWhere('content', 'LIKE', "%$lowerQuery%")
                ->orWhere('tag', 'LIKE', "%$lowerQuery%")
            ),
            default: fn ($q) => $q->whereFullText(['title', 'content', 'tag'], $query)
        )
            ->with('topic')
            ->get();
    }

    private function supportsFulltext(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql']);
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
            'questions.id', 'questions.title', 'questions.slug', 'questions.topic_id',
        ])
            ->with(['topic:id,name'])
            ->join('bookmarks', 'bookmarks.question_id', '=', 'questions.id')
            ->where('bookmarks.user_id', $userId)
            ->get();
    }
}
