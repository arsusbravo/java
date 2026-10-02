<?php

namespace App\Models;

use App\Models\Concerns\HasAffiliateLink;
use App\Models\Concerns\HasFeaturedImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasAffiliateLink, HasFactory, HasFeaturedImage;

    protected $placeholderImage = 'destination';

    protected $fillable = [
        'destination_id',
        'name',
        'slug',
        'description',
        'cuisine_type',
        'price_range',
        'address',
        'latitude',
        'longitude',
        'phone',
        'opening_hours',
        'affiliate_link',
        'affiliate_network_id',
        'average_rating',
        'is_featured',
        'status',
        'views_count',
        'clicks_count',
        'meta_title',
        'meta_description',
        'is_active',
    ];

    protected $casts = [
        'cuisine_type' => 'array',
        'opening_hours' => 'array',
        'is_featured' => 'boolean',
        'average_rating' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function destinations()
    {
        return $this->belongsToMany(Destination::class);
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