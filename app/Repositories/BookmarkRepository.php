<?php

namespace App\Repositories;

use App\Models\Bookmark;
use Illuminate\Support\Collection;

class BookmarkRepository
{
    public function toggle(int $questionId, int $userId): bool
    {
        $bookmark = Bookmark::whereUserId($userId)
            ->where('question_id', $questionId)
            ->first();

        if ($bookmark) {
            $bookmark->delete();

            return false;
        }

        Bookmark::create([
            'user_id' => $userId,
            'question_id' => $questionId,
        ]);

        return true;
    }

    public function getUserBookmarks(int $userId): Collection
    {
        return Bookmark::whereUserId($userId)
            ->with('question:id,topic_id,title,slug')
            ->get()
            ->pluck('question');
    }
}
