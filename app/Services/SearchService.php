<?php

namespace App\Services;

use App\DTOs\SearchResultDTO;
use App\Repositories\QuestionRepository;
use Illuminate\Support\Collection;

readonly class SearchService
{
    public function __construct(private QuestionRepository $questionRepository)
    {
    }

    public function search(string $query): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        $questions = $this->questionRepository->search($query);

        return $questions->load(['topic:id,name,slug'])
            ->map(fn($question) => SearchResultDTO::fromQuestion(
                $question,
                $this->getExcerpt($question->content, $query)
            ));
    }

    private function getExcerpt(
        string $content,
        string $query,
        int $length = 200
    ): string
    {
        $stripped = $this->stripContent($content);
        $matchPos = $this->findMatchPosition($stripped, $query);

        if ($matchPos !== false) {
            return $this->extractAroundMatch($stripped, $matchPos, $length);
        }

        return $this->extractFromOriginal($content, $query, $length)
            ?? $this->getDefaultExcerpt($stripped, $length);
    }

    private function stripContent(string $content): string
    {
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        return $content;
    }

    private function findMatchPosition(string $content, string $query): int|false
    {
        return mb_stripos(mb_strtolower($content), mb_strtolower($query));
    }

    private function extractFromOriginal(
        string $originalContent,
        string $query,
        int $length
    ): ?string
    {
        $matchPos = $this->findMatchPosition($originalContent, $query);
        if ($matchPos === false) {
            return null;
        }

        $excerpt = $this->extractAroundPosition($originalContent, $matchPos, $length);
        $excerpt = $this->stripContent($excerpt);
        $originalLength = mb_strlen($originalContent);

        return $this->addEllipsis(
            $excerpt,
            $matchPos > 0,
            $matchPos + mb_strlen($query) < $originalLength
        );
    }

    private function getDefaultExcerpt(string $content, int $length): string
    {
        if (mb_strlen($content) <= $length) {
            return $content;
        }

        return mb_substr($content, 0, $length) . '...';
    }

    private function extractAroundMatch(
        string $content,
        int $matchPos,
        int $length
    ): string
    {
        $excerpt = $this->extractAroundPosition($content, $matchPos, $length);
        $contentLength = mb_strlen($content);

        return $this->addEllipsis(
            $excerpt,
            $matchPos > 0,
            $matchPos < $contentLength
        );
    }

    private function extractAroundPosition(
        string $content,
        int $matchPos,
        int $length
    ): string
    {
        $contentLength = mb_strlen($content);
        $start = max(0, $matchPos - (int)($length / 2));
        $end = min($contentLength, $start + $length);

        if ($end - $start < $length) {
            $start = max(0, $end - $length);
        }

        return mb_substr($content, $start, $end - $start);
    }

    private function addEllipsis(
        string $excerpt,
        bool $prefix = false,
        bool $suffix = false
    ): string
    {
        if ($prefix) {
            $excerpt = "...$excerpt";
        }

        if ($suffix) {
            $excerpt = "$excerpt...";
        }

        return $excerpt;
    }
}
