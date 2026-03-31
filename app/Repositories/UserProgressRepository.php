<?php

namespace App\Repositories;

use App\Models\UserProgress;
use Illuminate\Support\Collection;

class UserProgressRepository
{
    public function getUserProgress(?int $userId): Collection
    {
        return UserProgress::query()
            ->where([
                'user_id' => $userId,
                'completed' => true
            ])
            ->with('question:id,topic_id')
            ->get();
    }

    public function getUserProgressByQuestion(Collection $questionIds, ?int $userId): array
    {
        return UserProgress::query()
            ->where('user_id', $userId)
            ->whereIn('question_id', $questionIds)
            ->pluck('completed', 'question_id')
            ->toArray();
    }
}
