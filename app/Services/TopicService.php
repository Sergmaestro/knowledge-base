<?php

namespace App\Services;

use App\Models\UserProgress;
use App\Repositories\TopicRepository;
use App\Repositories\UserProgressRepository;
use Illuminate\Support\Collection;

class TopicService
{
    public function __construct(
        private readonly UserProgressRepository $userProgressRepository,
        private readonly TopicRepository $topicRepository
    )
    {
    }

    public function getAllWithProgress(?int $userId): Collection
    {
        $topics = $this->topicRepository->getOrderedByIndex();
        $progressByTopic = [];
        if ($userId) {
            $progressByTopic = $this->userProgressRepository->getUserProgress($userId)
                ->groupBy(fn($progress) => $progress->question->topic_id)
                ->map(fn($progress) => $progress->count())
                ->toArray();
        }

        return $topics->map(function ($topic) use ($progressByTopic) {
            return [
                ...$topic->toArray(),
                'question_count' => $topic->questions_count,
                'progress' => $topic->questions_count > 0 && $progressByTopic
                    ? [
                        'completed' => $progressByTopic[$topic->id] ?? 0,
                        'total' => $topic->questions_count,
                    ]
                    : null
            ];
        });
    }
}
