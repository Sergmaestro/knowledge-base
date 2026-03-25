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
}
