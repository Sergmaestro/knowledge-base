<?php

namespace App\Services;

use App\DTOs\QuestionDTO;
use App\Models\Topic;
use App\Models\User;
use App\Repositories\AnswerNoteRepository;
use App\Repositories\BookmarkRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\UserProgressRepository;
use Illuminate\Support\Collection;

readonly class QuestionService
{
    public function __construct(
        private QuestionRepository     $repository,
        private UserProgressRepository $progressRepository,
        private BookmarkRepository     $bookmarkRepository,
        private AnswerNoteRepository   $noteRepository
    )
    {
    }

    public function getQuestionForUser(string $slug, ?int $userId): QuestionDTO
    {
        $question = $this->repository->findBySlug($slug);
        $navigation = $this->repository->getNeighbors($question);

        $userData = [
            'navigation' => $navigation,
            'is_completed' => false,
            'is_bookmarked' => false,
            'notes' => [],
        ];

        if ($userId) {
            $userData['is_completed'] = $this->progressRepository->getCompletedForQuestion($question->id, $userId) ?? false;
            $userData['is_bookmarked'] = $this->bookmarkRepository->exists($question->id, $userId);
            $userData['notes'] = $this->noteRepository->getForQuestion($question->id, $userId);
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
