<?php

namespace App\Models\Concerns;

trait HasIconMedia
{
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')->singleFile();
    }

    public function getIconUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('icon');

        return $media ? url($media->getUrl()) : null;
    }
}
