<?php

namespace App\Repositories;

use App\Models\AnswerNote;
use Illuminate\Support\Collection;

class AnswerNoteRepository
{
    public function create(array $noteData, int $userId): AnswerNote
    {
        return AnswerNote::create([
            'user_id' => $userId,
            ...$noteData,
        ]);
    }

    public function update(AnswerNote $note, string $newNote): bool
    {
        return $note->update(['note' => $newNote]);
    }

    public function delete(AnswerNote $note): void
    {
        $note->delete();
    }

    public function getForQuestion(int $questionId, int $userId): Collection
    {
        return AnswerNote::select(['id', 'note', 'created_at'])
            ->where([
                'user_id' => $userId,
                'question_id' => $questionId
            ])
            ->get();
    }
}
