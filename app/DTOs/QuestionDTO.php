<?php

namespace App\DTOs;

use App\Models\Question;
use Illuminate\Support\Collection;

readonly class QuestionDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $tag,
        public string $slug,
        public string $content,
        public array $topic,
        public array $navigation, // prev/next
        public bool $is_completed,
        public bool $is_bookmarked,
        public Collection $notes,
    ) {}

    public static function fromModel(Question $question, array $extra): self
    {
        return new self(
            id: $question->id,
            title: $question->title,
            tag: $question->tag,
            slug: $question->slug,
            content: $question->content,
            topic: [
                'name' => $question->topic->name,
                'slug' => $question->topic->slug,
            ],
            navigation: $extra['navigation'],
            is_completed: $extra['is_completed'] ?? false,
            is_bookmarked: $extra['is_bookmarked'] ?? false,
            notes: $extra['notes'] ?? collect(),
        );
    }
}
