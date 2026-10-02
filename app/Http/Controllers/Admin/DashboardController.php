<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Admin;
use App\Http\Controllers\Controller;
use App\Models\AffiliateClick;
use App\Models\Article;
use App\Models\Review;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $counts = collect(Admin::RESOURCES)->map(fn ($class) => [
            'key' => $class::$key,
            'label' => $class::$label,
            'icon' => $class::$icon,
            'count' => $class::$model::count(),
        ])->values();

        return Inertia::render('Dashboard', [
            'counts' => $counts,
            'stats' => [
                'pendingReviews' => Review::pending()->count(),
                'draftArticles' => Article::where('status', 'draft')->count(),
                'clicksLast30Days' => AffiliateClick::where('clicked_at', '>=', now()->subDays(30))->count(),
                'articleViews' => (int) Article::sum('views_count'),
            ],
            'pendingReviews' => Review::pending()->with('reviewable')->latest()->take(5)->get()->map(fn (Review $review) => [
                'id' => $review->id,
                'user_name' => $review->user_name,
                'rating' => $review->rating,
                'comment' => str($review->comment)->limit(120)->toString(),
                'item' => $review->reviewable?->name,
            ]),
            'topArticles' => Article::published()->orderByDesc('views_count')->take(5)->get(['id', 'title', 'views_count']),
        ]);
    }
}
