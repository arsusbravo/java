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
            // Booking clicks over the last 30 days (recorded by /go links)
            'topClicked' => AffiliateClick::where('clicked_at', '>=', now()->subDays(30))
                ->selectRaw('clickable_type, clickable_id, count(*) as clicks')
                ->groupBy('clickable_type', 'clickable_id')
                ->orderByDesc('clicks')
                ->limit(5)
                ->with('clickable')
                ->get()
                ->map(fn (AffiliateClick $click) => [
                    'name' => $click->clickable?->name ?? '(deleted)',
                    'type' => class_basename($click->clickable_type),
                    'clicks' => (int) $click->clicks,
                    'editUrl' => $click->clickable ? '/admin/' . str(class_basename($click->clickable_type))->plural()->lower() . '/' . $click->clickable_id . '/edit' : null,
                ]),
            'clicksBySource' => AffiliateClick::where('clicked_at', '>=', now()->subDays(30))
                ->selectRaw('source, count(*) as clicks')
                ->groupBy('source')
                ->orderByDesc('clicks')
                ->get()
                ->map(fn ($row) => [
                    'source' => AffiliateClick::SOURCES[$row->source] ?? 'Other',
                    'clicks' => (int) $row->clicks,
                ]),
            'topArticles' => Article::published()->orderByDesc('views_count')->take(5)->get(['id', 'title', 'views_count']),
        ]);
    }
}
