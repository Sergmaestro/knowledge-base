<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'order_index',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_index');
    }

    public function getQuestionCountAttribute(): int
    {
        return $this->questions()->count();
    }
}
