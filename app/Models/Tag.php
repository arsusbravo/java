<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
    ];

    // Relationships
    public function accommodations()
    {
        return $this->morphedByMany(Accommodation::class, 'taggable');
    }

    public function tours()
    {
        return $this->morphedByMany(Tour::class, 'taggable');
    }

    public function restaurants()
    {
        return $this->morphedByMany(Restaurant::class, 'taggable');
    }

    public function articles()
    {
        return $this->morphedByMany(Article::class, 'taggable');
    }
}