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
        private TopicRepository        $topicRepository
    )
    {
    }

    public function getAllWithProgress(?int $userId): Collection
    {
        $topics = $this->topicRepository->getOrderedByIndex();
        $progressByTopic = [];
        if ($userId) {
            $progressByTopic = $this->userProgressRepository->getUserProgressByTopic($userId);
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

    public function getTopicForUser(
        string $slug,
        ?int $userId,
        ?array $progressData
    ): TopicDTO
    {
        $topic = $this->topicRepository->findBySlug($slug);
        $progressByQuestion = [];

        if ($userId) {
            $progressByQuestion = $this->userProgressRepository->getUserProgressByQuestion($userId);
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
            $progressData
        );
    }
}
