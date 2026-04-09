<?php

namespace App\Console\Commands;

use App\Models\AnswerNote;
use App\Models\Bookmark;
use App\Models\Question;
use App\Models\Topic;
use App\Models\UserProgress;
use App\Repositories\UserProgressRepository;
use App\Services\MarkdownLoader;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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
            DB::transaction(function () {
                Question::truncate();
                Topic::truncate();

                // Reset all user-related data as it might be referenced to the wrong question
                AnswerNote::truncate();
                Bookmark::truncate();
                UserProgress::truncate();
                Cache::tags([UserProgressRepository::CACHE_TAG])->flush();
            });

            DB::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Loading markdown files...');
        $this->loader->loadMarkdownDirectory();

        $topicCount = Topic::count();
        $questionCount = Question::count();

        $this->info("Loaded $topicCount topics and $questionCount questions.");

        return Command::SUCCESS;
    }
}
