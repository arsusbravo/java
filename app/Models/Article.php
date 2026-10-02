<?php

namespace App\Models;

use App\Models\Concerns\HasFeaturedImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory, HasFeaturedImage;

    protected $placeholderImage = 'article';

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'article_type',
        'is_featured',
        'status',
        'published_at',
        'views_count',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function destinations()
    {
        return $this->belongsToMany(Destination::class);
    }

    public function accommodations()
    {
        return $this->belongsToMany(Accommodation::class, 'article_accommodation');
    }

    public function tours()
    {
        return $this->belongsToMany(Tour::class);
    }

    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('article_type', $type);
    }

    // Helper Methods
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Get the URL for this article
     */
    public function url()
    {
        return route('articles.show', [$this->id, $this->slug]);
    }

    /**
     * Get reading time in minutes
     */
    public function readingTime()
    {
        return ceil(str_word_count(strip_tags($this->content)) / 200);
    }
}