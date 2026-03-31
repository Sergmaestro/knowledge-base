<?php

namespace App\Services;

use App\DTOs\TopicDTO;
use App\Repositories\TopicRepository;
use App\Repositories\UserProgressRepository;
use Illuminate\Support\Collection;

readonly class TopicService
{
    public function __construct(
        private UserProgressRepository $userProgressRepository,
        private TopicRepository $topicRepository
    ) {}

    public function getAllWithProgress(?int $userId): Collection
    {
        $topics = $this->topicRepository->getOrderedByIndex();
        $progressByTopic = [];
        if ($userId) {
            $progressByTopic = $this->userProgressRepository->getUserProgress($userId)
                ->groupBy(fn ($progress) => $progress->question->topic_id)
                ->map(fn ($progress) => $progress->count())
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
                    : null,
            ];
        });
    }

    public function getTopicForUser(string $slug, ?int $userId): TopicDTO
    {
        $topic = $this->topicRepository->findBySlug($slug);
        $progressByQuestion = [];

        if ($userId) {
            $questionIds = $topic->questions->pluck('id');
            $progressByQuestion = $this->userProgressRepository->getUserProgressByQuestion($questionIds, $userId);
        }

        $questionsData = $topic->questions->map(function ($question) use ($progressByQuestion) {
            return [
                'id' => $question->id,
                'title' => $question->title,
                'slug' => $question->slug,
                'tag' => $question->tag,
                'order_index' => $question->order_index,
                'is_completed' => $progressByQuestion[$question['id']] ?? false,
            ];
        });

        return TopicDTO::fromModel(
            $topic,
            $questionsData->toArray(),
            $this->getProgressData($questionsData, $userId)
        );
    }

    private function getProgressData(Collection $questionsData, ?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }
        return [
            'completed' => $questionsData->where('is_completed', true)->count(),
            'total' => $questionsData->count(),
        ];
    }
}
