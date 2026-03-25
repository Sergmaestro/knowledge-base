<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProgressController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'completed' => 'required|boolean',
        ]);

        $user = $request->user();

        $progress = UserProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'question_id' => $request->question_id,
            ],
            [
                'completed' => $request->completed,
                'completed_at' => $request->completed ? now() : null,
            ]
        );

        return response()->json([
            'success' => true,
            'completed' => $progress->completed,
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        $user = $request->user();
        $questionId = $request->question_id;

        $progress = $user->progress()->where('question_id', $questionId)->first();

        if ($progress) {
            $progress->update([
                'completed' => !$progress->completed,
                'completed_at' => !$progress->completed ? now() : null,
            ]);
            $completed = $progress->completed;
        } else {
            $progress = UserProgress::create([
                'user_id' => $user->id,
                'question_id' => $questionId,
                'completed' => true,
                'completed_at' => now(),
            ]);
            $completed = true;
        }

        return response()->json([
            'success' => true,
            'completed' => $completed,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $topicId = $request->get('topic_id');

        $query = $request->user()->progress()->with('question');

        if ($topicId) {
            $query->whereHas('question', fn($q) => $q->where('topic_id', $topicId));
        }

        $progress = $query->get()->pluck('completed', 'question_id');

        return response()->json($progress);
    }
}
