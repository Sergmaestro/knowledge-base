<?php

namespace App\DTOs;

readonly class SearchResultDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $tag,
        public string $excerpt,
        public array $topic,
    ) {}

    public static function fromQuestion($question, string $excerpt): self
    {
        return new self(
            id: $question->id,
            title: $question->title,
            slug: $question->slug,
            tag: $question->tag,
            excerpt: $excerpt,
            topic: [
                'name' => $question->topic->name,
                'slug' => $question->topic->slug,
            ],
        );
    }
}
