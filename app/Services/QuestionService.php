<?php

namespace App\Services;

use App\DTOs\QuestionDTO;
use App\Models\Topic;
use App\Models\User;
use App\Repositories\QuestionRepository;
use App\Repositories\UserProgressRepository;
use Illuminate\Support\Collection;

class QuestionService
{
    public function __construct(
        private readonly QuestionRepository     $repository,
        private readonly UserProgressRepository $progressRepository
    )
    {
    }

    public function getQuestionForUser(string $slug, ?User $user): QuestionDTO
    {
        $question = $this->repository->findBySlug($slug);
        $navigation = $this->repository->getNeighbors($question);

        $userData = [
            'navigation' => $navigation,
            'is_completed' => false,
            'is_bookmarked' => false,
            'notes' => [],
        ];

        if ($user) {
            $userData['is_completed'] = $user->progress()
                ->where('question_id', $question->id)
                ->value('completed') ?? false;

            $userData['is_bookmarked'] = $user->bookmarks()
                ->where('question_id', $question->id)
                ->exists();

            $userData['notes'] = $user->notes()
                ->where('question_id', $question->id)
                ->get(['id', 'note', 'created_at'])
                ->toArray();
        }

        return QuestionDTO::fromModel($question, $userData);
    }

    public function getAllByTopicWithProgress(Topic $topic, ?int $userId): Collection
    {
        $progressByQuestion = [];
        if ($userId) {
            $questionIds = $topic->questions->pluck('id');
            $progressByQuestion = $this->progressRepository->getUserProgressByQuestion($questionIds, $userId);
        }

        return $topic->questions->map(function ($question) use ($progressByQuestion) {
            $question->is_completed = $progressByQuestion[$question->id] ?? false;
            return $question;
        });
    }

    public function getProgressStats(Collection $questions, ?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        return [
            'completed' => $questions->where('is_completed', true)->count(),
            'total' => $questions->count(),
        ];
    }
}
