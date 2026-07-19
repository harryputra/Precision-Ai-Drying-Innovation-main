<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KnowledgeBase extends Model
{
    protected $fillable = [
        'category',
        'title',
        'slug',
        'content',
        'tags',
        'metadata',
        'is_active',
        'use_for_ai',
        'priority_weight',
        'version',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tags'            => 'array',
            'metadata'        => 'array',
            'is_active'       => 'boolean',
            'use_for_ai'      => 'boolean',
            'priority_weight' => 'decimal:2',
            'version'         => 'integer',
        ];
    }

    // Auto-generate slug
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });
    }

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAi($query)
    {
        return $query->where('use_for_ai', true)->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }
}
