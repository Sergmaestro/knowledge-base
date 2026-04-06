<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleProgressRequest;
use App\Repositories\UserProgressRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __construct(
        private readonly UserProgressRepository $progressRepository
    )
    {
    }

    public function toggle(ToggleProgressRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'completed' => $this->progressRepository->toggle(
                $request->question_id,
                $request->user()->id
            ),
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $this->progressRepository->reset($request->user()->id);
        return response()->json(['success' => true]);
    }
}
