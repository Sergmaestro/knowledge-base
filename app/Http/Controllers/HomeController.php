<?php

namespace App\Http\Controllers;

use App\Services\TopicService;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(
        private readonly TopicService $topicService
    )
    {
    }

    public function index()
    {
        return Inertia::render(
            'Home',
            ['topics' => $this->topicService->getAllWithProgress(auth()->id())]
        );
    }
}
