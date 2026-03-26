<?php

namespace App\Http\Controllers;

use App\Repositories\QuestionRepository;
use App\Repositories\TopicRepository;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function __construct(
        private readonly QuestionRepository $questionRepository,
        private readonly TopicRepository $topicRepository
    ) {}

    public function show(string $slug)
    {
        $question = $this->questionRepository->findBySlug($slug);
        $userData = $this->questionRepository->getUserProgressData($question);
        $adjacent = $this->questionRepository->getAdjacentQuestions($question);

        return Inertia::render('Question', [
            'question' => $this->questionRepository->toResource($question, $userData, $adjacent),
            'next_question' => $adjacent['next'],
            'prev_question' => $adjacent['prev'],
            'topics' => $this->topicRepository->getAllWithProgress(),
        ]);
    }
}
