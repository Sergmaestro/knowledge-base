<?php

namespace App\Repositories;

use App\Models\Topic;
use App\Models\UserProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TopicRepository
{
    public function getAllWithProgress(): Collection
    {
        $topics = Topic::withCount('questions')
            ->orderBy('order_index')
            ->get();

        $progressByTopic = [];
        if (Auth::check()) {
            $progressByTopic = UserProgress::query()
                ->where('user_id', Auth::id())
                ->where('completed', true)
                ->whereHas('question')
                ->with('question:id,topic_id')
                ->get()
                ->groupBy(fn ($progress) => $progress->question->topic_id)
                ->map(fn ($progress) => $progress->count())
                ->toArray();
        }

        return $topics->map(function ($topic) use ($progressByTopic) {
            return [
                'id' => $topic->id,
                'name' => $topic->name,
                'slug' => $topic->slug,
                'questions_count' => $topic->questions_count,
                'progress' => $topic->questions_count > 0 && $progressByTopic ? [
                    'completed' => $progressByTopic[$topic->id] ?? 0,
                    'total' => $topic->questions_count,
                ] : null,
            ];
        });
    }

    public function findBySlug(string $slug): Topic
    {
        return Topic::where('slug', $slug)
            ->with(['questions' => function ($query) {
                $query->select('id', 'topic_id', 'title', 'slug', 'order_index');
            }])
            ->firstOrFail();
    }

    public function getQuestionsWithProgress(Topic $topic): Collection
    {
        $questionIds = $topic->questions->pluck('id');
        $progressByQuestion = [];

        if (Auth::check()) {
            $progressByQuestion = UserProgress::query()
                ->where('user_id', Auth::id())
                ->whereIn('question_id', $questionIds)
                ->pluck('completed', 'question_id')
                ->toArray();
        }

        return $topic->questions->map(function ($question) use ($progressByQuestion) {
            $question->is_completed = $progressByQuestion[$question->id] ?? false;

            return $question;
        });
    }

    public function getProgressStats(Collection $questions): ?array
    {
        if (! Auth::check()) {
            return null;
        }

        $completed = $questions->where('is_completed', true)->count();
        $total = $questions->count();

        return [
            'completed' => $completed,
            'total' => $total,
        ];
    }

    public function upsert(array $data): void
    {
        Topic::upsert($data, ['slug'], ['name', 'description', 'icon', 'order_index', 'updated_at']);
    }

    public function getIdBySlug(array $slugs): array
    {
        return Topic::whereIn('slug', $slugs)->pluck('id', 'slug')->toArray();
    }
}
