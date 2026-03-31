<?php

namespace App\Repositories;

use App\Models\Topic;
use App\Models\UserProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TopicRepository
{
    public function getOrderedByIndex(): Collection
    {
        return Topic::withCount('questions')
            ->orderBy('order_index')
            ->get();
    }

    public function findBySlug(string $slug): Topic
    {
        return Topic::where('slug', $slug)
            ->with(['questions:id,topic_id,title,slug,tag,order_index'])
            ->firstOrFail();
    }

    public function upsert(array $data): void
    {
        Topic::upsert($data, ['slug'], ['name', 'description', 'icon', 'order_index', 'updated_at']);
    }

    public function getIdBySlug(array $slugs): array
    {
        return Topic::whereIn('slug', $slugs)->pluck('id', 'slug')->toArray();
    }
}
