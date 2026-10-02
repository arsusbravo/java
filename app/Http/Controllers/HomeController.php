<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\Destination;
use App\Models\Review;
use App\Models\Tour;

class HomeController extends Controller
{
    public function index()
    {
        $homeFeaturedArticles = Article::published()
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();
        $homeFeaturedDestinations = Destination::where('is_featured', true)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
        
        // Get featured reviews (5 stars, approved, from popular destinations)
        $homeFeaturedReviews = Review::with('reviewable')
            ->where('status', 'approved')
            ->where('rating', 5)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $stats = [
            'destinations' => Destination::count(),
            'articles' => Article::published()->count(),
            'accommodations' => Accommodation::where('status', 'published')->count(),
            'tours' => Tour::where('status', 'published')->count(),
            'reviews' => Review::where('status', 'approved')->count(),
            'average_rating' => Review::where('status', 'approved')->avg('rating') ?? 0,
        ];

        return view('front.pages.home', compact('homeFeaturedArticles', 'homeFeaturedDestinations', 'homeFeaturedReviews', 'stats'));
    }

    public function about()
    {
        return view('front.pages.about');
    }
}
