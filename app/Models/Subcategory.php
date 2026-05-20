<?php

namespace App\Models;

use App\Models\Concerns\HasIconMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Subcategory extends Model implements HasMedia
{
    use HasIconMedia, InteractsWithMedia {
        HasIconMedia::registerMediaCollections insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'category_id',
        'parent_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Subcategory::class, 'parent_id')
            ->orderBy('sort_order');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function isLeaf(): bool
    {
        return ! $this->activeChildren()->exists();
    }

    public function wouldCreateCycle(?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ($newParentId === $this->id) {
            return true;
        }

        $ancestor = self::query()->find($newParentId);
        while ($ancestor !== null) {
            if ($ancestor->id === $this->id) {
                return true;
            }
            $ancestor = $ancestor->parent;
        }

        return false;
    }

    protected $appends = ['icon_url'];
}
