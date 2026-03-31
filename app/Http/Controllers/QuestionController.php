<?php

namespace App\Http\Controllers;

use App\Services\QuestionService;
use App\Services\TopicService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function __construct(
        private readonly QuestionService $questionService,
        private readonly TopicService $topicService
    ) {}

    public function show(string $slug, Request $request): Response
    {
        $authUser = $request->user();

        return Inertia::render(
            'Question',
            [
                'question' => $this->questionService->getQuestionForUser($slug, $authUser),
                'topics' => $this->topicService->getAllWithProgress($authUser?->id),
            ]
        );
    }
}
