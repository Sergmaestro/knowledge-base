<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Question extends Model
{
    use Searchable;

    protected $fillable = [
        'topic_id',
        'title',
        'content',
        'slug',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'topic_id' => $this->topic_id,
        ];
    }

    public function searchableAs(): string
    {
        return 'questions_index';
    }
}
