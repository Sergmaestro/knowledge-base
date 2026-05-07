<?php

namespace App\Services;

use App\Repositories\QuestionRepository;
use App\Repositories\TopicRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class MarkdownLoader
{
    private const array ANSWER_HEADINGS = [
        '**Answer:**',
        '**Answer**',
        '**Answer:',
        '**Answer',
        '**Example Answer:**',
        '**Example Answer (STAR):**',
        '**Example Answer',
    ];

    private const array TOPIC_CONFIG = [
        'PHP' => ['description' => 'PHP fundamentals, advanced topics, and best practices', 'icon' => 'php'],
        'Laravel' => ['description' => 'Laravel framework, Eloquent, architecture, and testing', 'icon' => 'laravel'],
        'Vue' => ['description' => 'Vue.js fundamentals, composition API, and state management', 'icon' => 'vue'],
        'Javascript' => ['description' => 'JavaScript fundamentals, ES6+, async patterns, and browser APIs', 'icon' => 'javascript'],
        'Typescript' => ['description' => 'TypeScript type system, generics, decorators, and advanced patterns', 'icon' => 'typescript'],
        'Database' => ['description' => 'Database design, optimization, and advanced queries', 'icon' => 'server'],
        'System-Design' => ['description' => 'System architecture, design patterns, and scalability', 'icon' => 'system-design'],
        'DevOps' => ['description' => 'DevOps practices, monitoring, and infrastructure', 'icon' => 'cloud'],
        'Networking' => ['description' => 'HTTP protocols, web fundamentals, and network concepts', 'icon' => 'network'],
        'Behavioral' => ['description' => 'Behavioral interview questions and STAR method examples', 'icon' => 'chat-bubble-left-right'],
        'Coding' => ['description' => 'LeetCode approaches, algorithms, and problem-solving patterns', 'icon' => 'coding'],
    ];

    private const array DEFAULT_TOPIC_CONFIG = ['description' => '', 'icon' => 'folder'];

    private string $markdownPath;

    public function __construct(
        private readonly TopicRepository    $topicRepository,
        private readonly QuestionRepository $questionRepository
    )
    {
        $this->markdownPath = resource_path('markdown');
    }

    public function loadMarkdownDirectory(): array
    {
        $stats = ['topics' => 0, 'questions' => 0];

        if (!File::exists($this->markdownPath)) {
            Log::warning('Markdown directory not found', ['path' => $this->markdownPath]);
            return $stats;
        }

        $topicData = $this->collectTopicData();
        $questionData = $this->collectQuestionData($topicData);

        if (empty($topicData)) {
            return $stats;
        }

        $this->topicRepository->upsert($topicData);
        $this->persistQuestions($questionData);

        $stats = [
            'topics' => count($topicData),
            'questions' => count($questionData)
        ];

        Log::info('Markdown loaded successfully', $stats);

        return $stats;
    }

    private function collectTopicData(): array
    {
        $topicData = [];
        $directories = File::directories($this->markdownPath);
        $orderIndex = 0;

        foreach ($directories as $directory) {
            $topicName = basename($directory);
            $topicSlug = Str::slug($topicName);

            $config = self::TOPIC_CONFIG[$topicName] ?? self::DEFAULT_TOPIC_CONFIG;

            $topicData[] = [
                'slug' => $topicSlug,
                'name' => $topicName,
                'description' => $config['description'],
                'icon' => $config['icon'],
                'order_index' => $orderIndex++,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $topicData;
    }

    private function collectQuestionData(array $topicData): array
    {
        $questionData = [];
        $directories = File::directories($this->markdownPath);
        $topicSlugs = array_column($topicData, 'slug');
        $topicMap = array_flip($topicSlugs);

        foreach ($directories as $directory) {
            $topicSlug = Str::slug(basename($directory));

            $files = File::files($directory);
            $orderIndex = 0;

            $mdFiles = array_filter($files, fn($file) => $file->getExtension() === 'md');
            usort($mdFiles, $this->sortMarkdownFiles(...));

            foreach ($mdFiles as $file) {
                $tag = $file->getBasename('.md');
                $content = File::get($file->getPathname());
                $parsedQuestions = $this->parseQuestions($content);

                foreach ($parsedQuestions as $question) {
                    $questionData[] = [
                        'topic_id' => $topicMap[$topicSlug] ?? null,
                        '_topic_slug' => $topicSlug,
                        'slug' => $question['slug'],
                        'title' => $question['title'],
                        'content' => $question['content'],
                        'tag' => $tag,
                        'order_index' => $orderIndex++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        return $questionData;
    }

    private function persistQuestions(array $questionData): void
    {
        if (empty($questionData)) {
            return;
        }

        $topicSlugs = array_unique(array_column($questionData, '_topic_slug'));
        $topicIdMap = $this->topicRepository->getIdBySlug($topicSlugs);

        foreach ($questionData as &$question) {
            $question['topic_id'] = $topicIdMap[$question['_topic_slug']] ?? 0;
            unset($question['_topic_slug']);
        }
        unset($question);

        $this->questionRepository->upsert($questionData);
    }

    private function parseQuestions(string $content): array
    {
        $questions = [];
        $parts = preg_split('/(?=^## Question \d+:)/m', $content);

        foreach ($parts as $part) {
            if (trim($part) === '' || !preg_match('/^## Question \d+:/m', $part)) {
                continue;
            }

            $lines = explode("\n", $part);
            $title = $this->extractTitle($lines);

            if (empty($title)) {
                continue;
            }

            $questionContent = $this->extractContent($lines);

            $questions[] = [
                'title' => $title,
                'content' => implode("\n", $questionContent),
                'slug' => Str::slug($title),
            ];
        }

        return $questions;
    }

    private function extractTitle(array &$lines): string
    {
        $titleLine = array_shift($lines);

        return str_replace(':**', '', $titleLine)
                |> (fn($x) => preg_replace('/## Question \d+:/', '', $x))
                |> trim(...);
    }

    private function extractContent(array $lines): array
    {
        $contentStart = false;
        $questionContent = [];
        $foundAnswerMarker = false;

        foreach ($lines as $line) {
            if (preg_match('/^## Question \d+:/', $line)) {
                continue;
            }

            if (!$foundAnswerMarker && in_array(trim($line), self::ANSWER_HEADINGS)) {
                $contentStart = true;
                $foundAnswerMarker = true;
                continue;
            }

            if ($contentStart) {
                $questionContent[] = $line;
            }
        }

        return $questionContent;
    }

    /**
     * Fundamental questions should always go first.
     * Advanced should always be at the bottom of the list
     */
    private function sortMarkdownFiles(SplFileInfo $a, SplFileInfo $b): int
    {
        $nameA = $a->getFilename();
        $nameB = $b->getFilename();

        if ($nameA === 'fundamentals.md') return -1;
        if ($nameB === 'fundamentals.md') return 1;
        if ($nameA === 'advanced.md') return 1;
        if ($nameB === 'advanced.md') return -1;

        return $nameA <=> $nameB;
    }
}
