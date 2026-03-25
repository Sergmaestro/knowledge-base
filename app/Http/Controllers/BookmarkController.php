<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
            $bookmarked = false;
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'question_id' => $questionId,
            ]);
            $bookmarked = true;
        }

        return response()->json([
            'success' => true,
            'bookmarked' => $bookmarked,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()
            ->bookmarks()
            ->with(['question' => function ($query) {
                $query->select('id', 'topic_id', 'title', 'slug');
            }])
            ->get()
            ->pluck('question')
        );
    }
}
