<?php

namespace App\Http\Controllers;

use App\Services\TopicService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicService $topicService
    ) {}

    public function show(string $slug, Request $request)
    {
        $userId = $request->user()?->id;
        $topic = $this->topicService->getTopicForUser($slug, $userId);

        return Inertia::render('Topic', [
            'topic' => $topic,
            'topics' => $this->topicService->getAllWithProgress($userId),
        ]);
    }
}
