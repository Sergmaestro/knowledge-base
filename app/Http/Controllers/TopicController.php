<?php

namespace App\Http\Controllers;

use App\Repositories\TopicRepository;
use App\Services\QuestionService;
use App\Services\TopicService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TopicController extends Controller
{
    public function __construct(
        private readonly QuestionService $questionService,
        private readonly TopicService $topicService,
        private readonly TopicRepository $topicRepository
    ) {}

    public function show(string $slug, Request $request)
    {
        $userId = $request->user()?->id;
        $topic = $this->topicRepository->findBySlug($slug);
        $questions = $this->questionService->getAllByTopicWithProgress($topic, $userId);
        $progress = $this->questionService->getProgressStats($questions, $userId);

        return Inertia::render('Topic', [
            'topic' => $topic->only(['id', 'name', 'slug', 'description', 'icon']),
            'questions' => $questions,
            'progress' => $progress,
            'topics' => $this->topicService->getAllWithProgress($userId)
        ]);
    }
}
