<?php

namespace App\Services;

use App\DTOs\QuestionDTO;
use App\Repositories\AnswerNoteRepository;
use App\Repositories\BookmarkRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\UserProgressRepository;

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

        $userData = [
            'navigation' => $this->repository->getNeighbors($question),
            'is_completed' => false,
            'is_bookmarked' => false,
            'notes' => collect(),
        ];

        if ($userId) {
            $userData['is_completed'] = $this->progressRepository->getCompletedForQuestion($question->id, $userId) ?? false;
            $userData['is_bookmarked'] = $this->bookmarkRepository->exists($question->id, $userId);
            $userData['notes'] = $this->noteRepository->getForQuestion($question->id, $userId);
        }

        return QuestionDTO::fromModel($question, $userData);
    }
}
