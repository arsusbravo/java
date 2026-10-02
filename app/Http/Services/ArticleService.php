<?php 

namespace App\Http\Services;

use App\Models\Article;

class ArticleService
{
    /**
     * Get published articles with optional filters.
     */
    public function getPublishedArticles($filters = [])
    {
        $query = Article::published()->with('author');

        // Apply filters
        if (!empty($filters['type']) && is_array($filters['type'])) {
            $query->whereIn('article_type', $filters['type']);
        }

        if (!empty($filters['destination'])) {
            $query->whereHas('destinations', function($q) use ($filters) {
                $q->where('slug', $filters['destination']);
            });
        }

        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('excerpt', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('published_at', 'desc')->paginate(12)->withQueryString();
    }
}