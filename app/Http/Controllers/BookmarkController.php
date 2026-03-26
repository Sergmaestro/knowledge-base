<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        $user = $request->user();
        $questionId = $request->question_id;

        $bookmark = $user->bookmarks()->where('question_id', $questionId)->first();

        if ($bookmark) {
            $bookmark->delete();
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'question_id' => $questionId,
            ]);
        }

        return response()->json([
            'success' => true,
            'bookmarked' => (bool) $bookmark,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()
                ->bookmarks()
                ->with('question:id,topic_id,title,slug')
                ->get()
                ->pluck('question')
        );
    }
}
