<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Destination;
use App\Models\DestinationType;
use App\Models\Region;
use App\Support\Agoda\Stay;
use App\Support\Agoda\TripPricing;
use App\Support\TripPlanner;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripPlannerController extends Controller
{
    /**
     * The planner form, and the itinerary once a start place is chosen. All
     * choices live in the URL, so a trip can be shared or bookmarked.
     */
    public function show(Request $request, TripPlanner $planner, TripPricing $pricing): View
    {
        $types = DestinationType::ordered()->get();

        $choices = [
            'start' => (string) $request->query('start', ''),
            'days' => max(TripPlanner::MIN_DAYS, min(TripPlanner::MAX_DAYS, (int) $request->query('days', TripPlanner::DEFAULT_DAYS))),
            'interests' => array_values(array_intersect((array) $request->query('interests', []), $types->pluck('slug')->all())),
            'budget' => array_key_exists($request->query('budget'), TripPlanner::BUDGETS) ? $request->query('budget') : TripPlanner::DEFAULT_BUDGET,
            'skip' => array_values(array_filter((array) $request->query('skip', []), 'is_string')),
            'date' => '',
            'adults' => 2,
        ];

        // Optional start date: live hotel prices per night. Past or invalid dates are ignored.
        $requestedDate = (string) $request->query('date', '');
        $firstNight = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate) && strtotime($requestedDate)
            ? Stay::fromQuery(['checkin' => $requestedDate, 'checkout' => CarbonImmutable::parse($requestedDate)->addDay()->toDateString(), 'adults' => $request->query('adults', 2)])
            : null;
        if ($firstNight) {
            $choices['date'] = $firstNight->checkIn->toDateString();
            $choices['adults'] = $firstNight->adults;
        }

        $start = $choices['start'] !== '' ? Destination::where('slug', $choices['start'])->whereNotNull('latitude')->first() : null;
        $plan = $start ? $planner->plan($start, $choices['days'], $choices['interests'], $choices['budget'], $choices['skip']) : null;
        if ($plan && $firstNight) {
            $plan = $pricing->price($plan, $firstNight->checkIn, $firstNight->adults);
        }

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
            'dateIgnored' => $requestedDate !== '' && ! $firstNight,
            'plan' => $plan,
            'articles' => $articles,
        ]);
    }
}
