<?php

namespace App\Repositories;

use App\Models\AnswerNote;

class AnswerNoteRepository
{
    public function create(array $noteData, int $userId): AnswerNote
    {
        return AnswerNote::create([
            'user_id' => $userId,
            ...$noteData
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
}
