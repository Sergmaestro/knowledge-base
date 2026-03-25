<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Topic;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MarkdownLoader
{
    private string $markdownPath;

    public function __construct()
    {
        $this->markdownPath = resource_path('markdown');
    }

    public function loadMarkdownDirectory(): void
    {
        if (! File::exists($this->markdownPath)) {
            return;
        }

        $directories = File::directories($this->markdownPath);
        $orderIndex = 0;

        foreach ($directories as $directory) {
            $topicName = basename($directory);
            $topicSlug = Str::slug($topicName);

            $topic = Topic::firstOrCreate(
                ['slug' => $topicSlug],
                [
                    'name' => $topicName,
                    'description' => $this->getTopicDescription($topicName),
                    'icon' => $this->getTopicIcon($topicName),
                    'order_index' => $orderIndex++,
                ]
            );

            $this->loadQuestionsFromTopic($topic, $directory);
        }
    }

    private function loadQuestionsFromTopic(Topic $topic, string $directory): void
    {
        $files = File::files($directory);
        $orderIndex = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $content = File::get($file->getPathname());
            $questions = $this->parseQuestions($content);

            foreach ($questions as $questionData) {
                Question::updateOrCreate(
                    ['slug' => $questionData['slug']],
                    [
                        'topic_id' => $topic->id,
                        'title' => $questionData['title'],
                        'content' => $questionData['content'],
                        'order_index' => $orderIndex++,
                    ]
                );
            }
        }
    }

    private function parseQuestions(string $content): array
    {
        $questions = [];
        $parts = preg_split('/(?=^## Question \d+:)/m', $content);

        foreach ($parts as $part) {
            if (trim($part) === '' || ! preg_match('/^## Question \d+:/m', $part)) {
                continue;
            }

            $lines = explode("\n", $part);
            $titleLine = array_shift($lines);
            $title = trim(str_replace('## Question ', '', str_replace(':**', '', $titleLine)));

            $contentStart = false;
            $questionContent = [];
            $foundAnswerMarker = false;

            foreach ($lines as $line) {
                if (preg_match('/^## Question \d+:/', $line)) {
                    continue;
                }

                if (! $foundAnswerMarker && (trim($line) === '**Answer:**' || trim($line) === '**Answer:' || trim($line) === '**Example Answer:**' || trim($line) === '**Example Answer (STAR):**' || trim($line) === '**Example Answer')) {
                    $contentStart = true;
                    $foundAnswerMarker = true;

                    continue;
                }

                if ($contentStart) {
                    $questionContent[] = $line;
                }
            }

            $fullContent = implode("\n", $questionContent);
            $slug = Str::slug($title);

            $questions[] = [
                'title' => $title,
                'content' => $fullContent,
                'slug' => $slug,
            ];
        }

        return $questions;
    }

    private function getTopicDescription(string $topicName): string
    {
        return match ($topicName) {
            'PHP' => 'PHP fundamentals, advanced topics, and best practices',
            'Laravel' => 'Laravel framework, Eloquent, architecture, and testing',
            'Vue' => 'Vue.js fundamentals, composition API, and state management',
            'Database' => 'Database design, optimization, and advanced queries',
            'System-Design' => 'System architecture, design patterns, and scalability',
            'DevOps' => 'DevOps practices, monitoring, and infrastructure',
            'Behavioral' => 'Behavioral interview questions and STAR method examples',
            default => '',
        };
    }

    private function getTopicIcon(string $topicName): string
    {
        return match ($topicName) {
            'PHP' => 'code-bracket',
            'Laravel' => 'cube',
            'Vue' => 'eye',
            'Database' => 'server',
            'System-Design' => 'cube-transparent',
            'DevOps' => 'cloud',
            'Behavioral' => 'chat-bubble-left-right',
            default => 'folder',
        };
    }

    public function refreshContent(): void
    {
        $this->loadMarkdownDirectory();
    }
}
