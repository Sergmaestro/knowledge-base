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

    public function userNotes(): HasMany
    {
        return $this->hasMany(AnswerNote::class)->where('user_id', auth()->id());
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'tag' => $this->tag,
            'topic_id' => $this->topic_id,
        ];
    }

    public function toSearchableArrayWithRelations(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'tag' => $this->tag,
            'topic_id' => $this->topic_id,
            'topic_name' => $this->topic?->name,
            'topic_slug' => $this->topic?->slug,
        ];
    }

    public function searchableAs(): string
    {
        return 'questions_index';
    }
}
