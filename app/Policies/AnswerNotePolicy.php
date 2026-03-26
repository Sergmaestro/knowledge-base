<?php

namespace App\Policies;

use App\Models\AnswerNote;
use App\Models\User;

class AnswerNotePolicy
{
    public function update(User $user, AnswerNote $note): bool
    {
        return $user->id === $note->user_id;
    }

    public function delete(User $user, AnswerNote $note): bool
    {
        return $user->id === $note->user_id;
    }
}
