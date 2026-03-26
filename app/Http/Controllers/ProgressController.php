<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleProgressRequest;
use App\Http\Requests\UpdateProgressRequest;
use App\Repositories\ProgressRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __construct(
        private readonly ProgressRepository $progressRepository
    ) {}

    public function update(UpdateProgressRequest $request): JsonResponse
    {
        $progress = $this->progressRepository->update(
            $request->question_id,
            $request->completed
        );

        return response()->json([
            'success' => true,
            'completed' => $progress->completed,
        ]);
    }

    public function toggle(ToggleProgressRequest $request): JsonResponse
    {
        $completed = $this->progressRepository->toggle($request->question_id);

        return response()->json([
            'success' => true,
            'completed' => $completed,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->progressRepository->getUserProgress($request->topic_id)
        );
    }
}
