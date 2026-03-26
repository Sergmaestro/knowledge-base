<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\AnswerNote;
use App\Repositories\AnswerNoteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class AnswerNoteController extends Controller
{
    public function __construct(
        private readonly AnswerNoteRepository $noteRepository
    ) {}

    public function store(StoreNoteRequest $request): JsonResponse
    {
        $this->noteRepository->create(
            $request->input('question_id'),
            $request->input('note')
        );

        return response()->json(['success' => true, 'stage' => 'stored']);
    }

    #[Authorize('update', 'note')]
    public function update(UpdateNoteRequest $request, AnswerNote $note): JsonResponse
    {
        $this->noteRepository->update($note, $request->input('note'));

        return response()->json(['success' => true, 'stage' => 'updated']);
    }

    #[Authorize('delete', 'note')]
    public function destroy(AnswerNote $note): JsonResponse
    {
        $this->noteRepository->delete($note);

        return response()->json(['success' => true, 'stage' => 'deleted']);
    }
}
