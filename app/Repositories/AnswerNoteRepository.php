<?php

namespace App\Repositories;

use App\Models\AnswerNote;
use Illuminate\Support\Collection;

class AnswerNoteRepository
{
    public function create(int $questionId, string $note): AnswerNote
    {
        return AnswerNote::create([
            'user_id' => auth()->id(),
            'question_id' => $questionId,
            'note' => $note,
        ]);
    }

    public function update(AnswerNote $note, string $newNote): AnswerNote
    {
        $note->update(['note' => $newNote]);

        return $note->fresh();
    }

    public function delete(AnswerNote $note): void
    {
        $note->delete();
    }

    public function getNotesForQuestion(int $questionId): Collection
    {
        return auth()->user()->notes()
            ->where('question_id', $questionId)
            ->get()
            ->map(fn ($note) => [
                'id' => $note->id,
                'note' => $note->note,
                'created_at' => $note->created_at->toIsoString(),
            ]);
    }
}
