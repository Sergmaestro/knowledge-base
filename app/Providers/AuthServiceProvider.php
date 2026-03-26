<?php

namespace App\Providers;

use App\Models\AnswerNote;
use App\Policies\AnswerNotePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        AnswerNote::class => AnswerNotePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
