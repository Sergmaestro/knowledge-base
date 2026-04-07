<?php

namespace App\Repositories;

use App\Models\UserProgress;
use Illuminate\Support\Facades\Cache;

class UserProgressRepository
{
    private const int CACHE_TTL = 3600;

    public const string CACHE_TAG = 'user_progress';

    /**
     * User progress by topic
     * returns an array where:
     * key is `topic_id`
     * value is the number of completed questions
     */
    public function getUserProgressByTopic(int $userId): array
    {
        return Cache::tags([self::CACHE_TAG, (string) $userId])
            ->remember('by_topic', self::CACHE_TTL, function () use ($userId) {
                return UserProgress::selectRaw('questions.topic_id, COUNT(user_progress.id) as count')
                    ->join('questions', 'questions.id', '=', 'user_progress.question_id')
                    ->where('user_progress.user_id', $userId)
                    ->where('user_progress.completed', true)
                    ->groupBy('questions.topic_id')
                    ->pluck('count', 'topic_id')
                    ->toArray();
            });
    }

    /**
     * User progress by question
     * returns an array where:
     * key is `question_id`
     * value is `completed` boolean
     */
    public function getUserProgressByQuestion(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return Cache::tags([self::CACHE_TAG, (string) $userId])
            ->remember('by_questions', self::CACHE_TTL, function () use ($userId) {
                return UserProgress::whereUserId($userId)
                    ->pluck('completed', 'question_id')
                    ->toArray();
            });
    }

    /**
     * Check if the current question is completed (exists and completed in User Progress)
     */
    public function getCompletedForQuestion(int $questionId, int $userId): ?bool
    {
        return $this->getUserProgressByQuestion($userId)[$questionId] ?? null;
    }

    public function toggle(int $questionId, int $userId): bool
    {
        $this->invalidateCache($userId);

        $progress = UserProgress::whereUserId($userId)
            ->where('question_id', $questionId)
            ->first();

        if ($progress) {
            $isCompleted = !$progress->completed;
            $progress->update([
                'completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
            ]);

            return $isCompleted;
        }

        UserProgress::create([
            'user_id' => $userId,
            'question_id' => $questionId,
            'completed' => true,
            'completed_at' => now(),
        ]);

        return true;
    }

    public function reset(int $userId): void
    {
        $this->invalidateCache($userId);
        UserProgress::whereUserId($userId)->delete();
    }

    private function invalidateCache(int $userId): void
    {
        Cache::tags([self::CACHE_TAG, (string) $userId])->flush();
    }
}
