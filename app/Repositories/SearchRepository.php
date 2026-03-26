<?php

namespace App\Repositories;

use App\Models\Question;
use Illuminate\Support\Collection;

class SearchRepository
{
    public function search(string $query): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        $searchTerm = "%$query%";

        return Question::select('questions.*', 'topics.name as topic_name', 'topics.slug as topic_slug')
            ->join('topics', 'questions.topic_id', 'topics.id')
            ->where(function ($q) use ($searchTerm) {
                $q->whereLike('questions.title', $searchTerm)
                    ->orWhereLike('questions.content', $searchTerm);
            })
            ->limit(50)
            ->get()
            ->map(function ($question) {
                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'slug' => $question->slug,
                    'topic' => [
                        'name' => $question->topic_name,
                        'slug' => $question->topic_slug,
                    ],
                    'excerpt' => $this->getExcerpt($question->content, 200),
                ];
            });
    }

    private function getExcerpt(string $content, int $length = 200): string
    {
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        if (strlen($content) <= $length) {
            return $content;
        }

        return mb_substr($content, 0, $length).'...';
    }
}
