<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Article;
use App\Models\Destination;
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

        $stats = [
            'destinations' => Destination::count(),
            'articles' => Article::published()->count(),
            'accommodations' => Accommodation::where('status', 'published')->count(),
            'tours' => Tour::where('status', 'published')->count(),
        ];

        return view('front.pages.home', compact('homeFeaturedArticles', 'homeFeaturedDestinations', 'stats'));
    }

    public function about()
    {
        return view('front.pages.about');
    }
}
