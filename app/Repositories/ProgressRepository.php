<?php

namespace App\Repositories;

use App\Models\UserProgress;
use Illuminate\Support\Collection;

class ProgressRepository
{
    public function update(int $questionId, bool $completed): UserProgress
    {
        return UserProgress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'question_id' => $questionId,
            ],
            [
                'completed' => $completed,
                'completed_at' => $completed ? now() : null,
            ]
        );
    }

    public function toggle(int $questionId): bool
    {
        $progress = auth()->user()->progress()
            ->where('question_id', $questionId)
            ->first();

        if ($progress) {
            $progress->update([
                'completed' => ! $progress->completed,
                'completed_at' => ! $progress->completed ? now() : null,
            ]);

            return $progress->completed;
        }

        UserProgress::create([
            'user_id' => auth()->id(),
            'question_id' => $questionId,
            'completed' => true,
            'completed_at' => now(),
        ]);

        return true;
    }

    public function getUserProgress(?int $topicId = null): Collection
    {
        $query = auth()->user()->progress()->with('question');

        if ($topicId) {
            $query->whereHas('question', fn ($q) => $q->where('topic_id', $topicId));
        }

        return $query->get()->pluck('completed', 'question_id');
    }
}
