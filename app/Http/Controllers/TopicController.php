<?php

namespace App\Http\Controllers;

use App\Repositories\TopicRepository;
use Inertia\Inertia;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicRepository $topicRepository
    ) {}

    public function show(string $slug)
    {
        $topic = $this->topicRepository->findBySlug($slug);
        $questions = $this->topicRepository->getQuestionsWithProgress($topic);
        $progress = $this->topicRepository->getProgressStats($questions);

        return Inertia::render('Topic', [
            'topic' => [
                'id' => $topic->id,
                'name' => $topic->name,
                'slug' => $topic->slug,
                'description' => $topic->description,
                'icon' => $topic->icon,
            ],
            'questions' => $questions,
            'progress' => $progress,
            'topics' => $this->topicRepository->getAllWithProgress(),
        ]);
    }
}
