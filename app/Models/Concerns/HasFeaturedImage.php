<?php

namespace App\Models\Concerns;

trait HasFeaturedImage
{
    /**
     * Resolve the best image URL for this model.
     *
     * Prefers the `featured_image` column, then the featured or first uploaded
     * Image, then the model's placeholder. Files that don't exist are skipped.
     * Uses the eager-loaded `images` relation when available to avoid N+1 queries.
     */
    public function getFeaturedImageUrlAttribute(): string
    {
        $placeholder = $this->placeholderImage ?? 'destination';
        $placeholderUrl = image_url(null, $placeholder);

        if ($this->featured_image) {
            $url = image_url($this->featured_image, $placeholder);

            if ($url !== $placeholderUrl) {
                return $url;
            }
        }

        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();
        $image = $images->firstWhere('is_featured', true) ?? $images->sortBy('order')->first();

        return image_url($image?->path, $placeholder);
    }
}
