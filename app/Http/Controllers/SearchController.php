<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Repositories\TopicRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function apiIndex(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $searchTerm = "%$query%";

        $results = Question::select('questions.*', 'topics.name as topic_name', 'topics.slug as topic_slug')
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

        $json = json_encode([
            'results' => $results->toArray(),
        ], JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE);

        return response($json, 200, ['Content-Type' => 'application/json']);
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
