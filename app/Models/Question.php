<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Question extends Model
{
    use Searchable;

    protected $fillable = [
        'topic_id',
        'title',
        'content',
        'slug',
        'tag',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AnswerNote::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'slug' => $this->slug,
            'tag' => $this->tag
        ];
    }

    public function searchableAs(): string
    {
        return 'questions_index';
    }
}
