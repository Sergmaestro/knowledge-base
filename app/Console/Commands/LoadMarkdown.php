<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Topic;
use App\Services\MarkdownLoader;
use DB;
use Illuminate\Console\Command;

class LoadMarkdown extends Command
{
    protected $signature = 'markdown:load {--fresh : Clear existing data and reload}';

    protected $description = 'Load markdown files into the database';

    public function __construct(
        private readonly MarkdownLoader $loader
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->info('Clearing existing data...');
            DB::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS=0');
            Question::truncate();
            Topic::truncate();
            DB::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Loading markdown files...');
        $this->loader->loadMarkdownDirectory();

        $topicCount = Topic::count();
        $questionCount = Question::count();

        $this->info("Loaded {$topicCount} topics and {$questionCount} questions.");

        return Command::SUCCESS;
    }
}
