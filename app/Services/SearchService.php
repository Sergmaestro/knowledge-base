<?php

namespace App\Services;

use App\Repositories\QuestionRepository;
use Illuminate\Support\Collection;

class SearchService
{
    public function __construct(private readonly QuestionRepository $questionRepository)
    {
    }

    public function search(string $query): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        // Laravel Scout search
        $questions = $this->questionRepository->search($query);

        return $questions->load(['topic:id,name,slug'])
            ->map(function ($question) use ($query) {
                return [
                    ...$question->toSearchableArray(),
                    'topic' => $question->topic,
                    'excerpt' => $this->getExcerpt($question->content, $query, 200)
                ];
            });
    }

    private function getExcerpt(string $content, string $query, int $length = 200): string
    {
        // Clean content: remove HTML tags, normalize whitespace
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        // Ensure UTF-8 encoding
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        // Case-insensitive search for the query in content
        $queryLower = mb_strtolower($query);
        $contentLower = mb_strtolower($content);
        $matchPos = mb_stripos($contentLower, $queryLower);

        // No match found - return default excerpt from the beginning
        if ($matchPos === false) {
            if (mb_strlen($content) <= $length) {
                return $content;
            }

            return mb_substr($content, 0, $length) . '...';
        }

        // Match found - center excerpt around the match position
        // Calculate start position: go back half the length from match
        $start = max(0, $matchPos - (int)($length / 2));

        // Calculate end position: start + desired length
        $end = min(mb_strlen($content), $start + $length);

        // If end hit the content boundary, shift start back to keep full length
        if ($end - $start < $length) {
            $start = max(0, $end - $length);
        }

        // Extract the excerpt slice
        $excerpt = mb_substr($content, $start, $end - $start);

        // Add ellipsis if we're not at content boundaries
        if ($start > 0) {
            $excerpt = "...$excerpt";
        }

        if ($end < mb_strlen($content)) {
            $excerpt = "$excerpt...";
        }

        return $excerpt;
    }
}
