<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Destination;
use App\Models\DestinationType;
use App\Models\Region;
use App\Support\TripPlanner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripPlannerController extends Controller
{
    /**
     * The planner form, and the itinerary once a start place is chosen. All
     * choices live in the URL, so a trip can be shared or bookmarked.
     */
    public function show(Request $request, TripPlanner $planner): View
    {
        $types = DestinationType::ordered()->get();

        $choices = [
            'start' => (string) $request->query('start', ''),
            'days' => max(TripPlanner::MIN_DAYS, min(TripPlanner::MAX_DAYS, (int) $request->query('days', TripPlanner::DEFAULT_DAYS))),
            'interests' => array_values(array_intersect((array) $request->query('interests', []), $types->pluck('slug')->all())),
            'budget' => array_key_exists($request->query('budget'), TripPlanner::BUDGETS) ? $request->query('budget') : TripPlanner::DEFAULT_BUDGET,
            'skip' => array_values(array_filter((array) $request->query('skip', []), 'is_string')),
        ];

        $start = $choices['start'] !== '' ? Destination::where('slug', $choices['start'])->whereNotNull('latitude')->first() : null;
        $plan = $start ? $planner->plan($start, $choices['days'], $choices['interests'], $choices['budget'], $choices['skip']) : null;

        // Guides and tips about the places on the route
        $articles = $plan
            ? Article::published()
                ->whereHas('destinations', fn ($query) => $query->whereIn('destinations.id', collect($plan['days'])->flatMap(fn ($day) => collect($day['stops'])->pluck('destination.id'))))
                ->orderByDesc('published_at')
                ->take(4)
                ->get()
            : collect();

        return view('front.pages.trip-planner', [
            'regions' => Region::ordered()->with(['destinations' => fn ($query) => $query->whereNotNull('latitude')->orderBy('name')])->get(),
            'types' => $types,
            'budgets' => TripPlanner::BUDGETS,
            'choices' => $choices,
            'start' => $start,
            'startNotFound' => $choices['start'] !== '' && ! $start,
            'plan' => $plan,
            'articles' => $articles,
        ]);
    }
}
