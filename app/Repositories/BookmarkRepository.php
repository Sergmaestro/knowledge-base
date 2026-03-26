<?php

namespace App\Repositories;

use App\Models\Bookmark;
use Illuminate\Support\Collection;

class BookmarkRepository
{
    public function toggle(int $questionId): bool
    {
        $bookmark = auth()->user()->bookmarks()
            ->where('question_id', $questionId)
            ->first();

        if ($bookmark) {
            $bookmark->delete();

            return false;
        }

        Bookmark::create([
            'user_id' => auth()->id(),
            'question_id' => $questionId,
        ]);

        return true;
    }

    public function getUserBookmarks(): Collection
    {
        return auth()->user()
            ->bookmarks()
            ->with('question:id,topic_id,title,slug')
            ->get()
            ->pluck('question');
    }
}
