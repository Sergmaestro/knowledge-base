<?php

namespace App\Http\Controllers;

use App\Repositories\TopicRepository;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(
        private readonly TopicRepository $topicRepository
    ) {}

    public function index()
    {
        return Inertia::render(
            'Home',
            ['topics' => $this->topicRepository->getAllWithProgress()]
        );
    }
}
