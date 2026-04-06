<?php

namespace App\Services;

use App\Repositories\BookmarkRepository;
use App\Repositories\QuestionRepository;
use Illuminate\Support\Collection;

readonly class BookmarkService
{
    public function __construct(
        private BookmarkRepository $repository,
        private QuestionRepository $questionRepository,
    )
    {
    }

    public function toggle(int $questionId, int $userId): array
    {
        return [
            'success' => true,
            'bookmarked' => $this->repository->toggle($questionId, $userId),
        ];
    }

    public function getUserBookmarks(int $userId): Collection
    {
        return $this->questionRepository->getBookmarkedQuestions($userId);
    }
}
