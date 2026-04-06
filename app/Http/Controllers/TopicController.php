<?php

namespace App\Http\Controllers;

use App\Services\TopicService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicService $topicService
    )
    {
    }

    public function show(string $slug, Request $request): Response
    {
        $userId = $request->user()?->id;
        $topics = $this->topicService->getAllWithProgress($userId);
        $progressData = $topics->where('slug', $slug)->first()['progress'];
        $topic = $this->topicService->getTopicForUser($slug, $userId, $progressData);

        return Inertia::render('Topic', [
            'topic' => $topic,
            'topics' => $topics,
        ]);
    }
}
