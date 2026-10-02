<?php

namespace App\Models;

use App\Models\Concerns\HasAffiliateLink;
use App\Models\Concerns\HasFeaturedImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasAffiliateLink, HasFactory, HasFeaturedImage;

    protected $placeholderImage = 'destination';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'type',
        'duration',
        'duration_unit',
        'price_from',
        'currency',
        'difficulty_level',
        'max_group_size',
        'included',
        'not_included',
        'meeting_point',
        'affiliate_link',
        'affiliate_network_id',
        'commission_rate',
        'is_featured',
        'status',
        'views_count',
        'clicks_count',
        'meta_title',
        'meta_description',
        'is_active',
    ];

    protected $casts = [
        'included' => 'array',
        'not_included' => 'array',
        'is_featured' => 'boolean',
        'price_from' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function destinations()
    {
        return $this->belongsToMany(Destination::class)
            ->withPivot('is_primary');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function clicks()
    {
        return $this->morphMany(AffiliateClick::class, 'clickable');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function incrementClicks()
    {
        $this->increment('clicks_count');
    }
}