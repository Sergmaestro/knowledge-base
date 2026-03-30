<?php

namespace App\Services;

use App\DTOs\QuestionDTO;
use App\Models\User;
use App\Repositories\QuestionRepository;

class QuestionService
{
    public function __construct(
        protected QuestionRepository $repository
    ) {}

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
}
