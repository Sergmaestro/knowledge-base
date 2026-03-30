<?php

namespace App\Http\Controllers;

use App\Repositories\TopicRepository;
use App\Services\QuestionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function __construct(
        private readonly QuestionService $questionService,
        private readonly TopicRepository $topicRepository
    ) {}

    public function show(string $slug, Request $request): Response
    {
        $questionDTO = $this->questionService->getQuestionForUser(
            $slug,
            $request->user()
        );

        return Inertia::render(
            'Question',
            [
                'question' => $questionDTO,
                'topics' => $this->topicRepository->getAllWithProgress(),
            ]
        );
    }
}
