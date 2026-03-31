<?php

namespace App\DTOs;

use App\Models\Topic;

readonly class TopicDTO
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $slug,
        public string $description,
        public string $icon,
        public array  $questions,
        public ?array $progress,
    )
    {
    }

    public static function fromModel(
        Topic  $topic,
        ?array $questions = null,
        ?array $progress = null
    ): self
    {
        return new self(
            id: $topic->id,
            name: $topic->name,
            slug: $topic->slug,
            description: $topic->description,
            icon: $topic->icon,
            questions: $questions ?? [],
            progress: $progress,
        );
    }
}
