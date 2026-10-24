<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Region;
use App\Models\Restaurant;
use App\Support\Geo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class DestinationController extends Controller
{
    /**
     * Display a listing of all destinations.
     */
    public function index(Request $request)
    {
        // Get all regions for filter dropdown
        $regions = Region::with(['destinations' => function ($query) use ($request) {
            // Apply search filter
            if ($request->filled('search')) {
                $query->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('description', 'like', '%'.$request->search.'%');
                });
            }
            $query->orderBy('order')->orderBy('name');
        }])->ordered()->get();

        // Build search query for featured destinations
        $featuredQuery = Destination::with('region');

        // Apply filters
        if ($request->filled('search')) {
            $featuredQuery->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('region')) {
            $featuredQuery->whereHas('region', function ($query) use ($request) {
                $query->where('slug', $request->region);
            });
        }

        // Without filters show the featured selection; with filters show every match
        $featuredQuery->orderBy('order');
        if (! $request->filled('search') && ! $request->filled('region')) {
            $featuredQuery->where('is_featured', true)->take(6);
        }
        $featuredDestinations = $featuredQuery->get();

        // Get total destinations count
        $totalDestinations = Destination::count();

        // Get selected region for filter
        $selectedRegion = $request->region;
        $searchQuery = $request->search;

        return view('front.pages.destinations', compact(
            'regions',
            'totalDestinations',
            'featuredDestinations',
            'selectedRegion',
            'searchQuery'
        ));
    }

    /**
     * Display the specified destination.
     */
    public function show($regionSlug, $destinationSlug)
    {
        $destination = $this->findDestination($regionSlug, $destinationSlug);

        $destination->increment('views_count');

        // Get accommodations for this destination
        $accommodationLimit = 6;
        $accommodationRelations = ['images', 'reviews', 'affiliateNetwork'];
        $accommodations = $destination->accommodations()
            ->with($accommodationRelations)
            ->published()
            ->where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('star_rating', 'desc')
            ->limit($accommodationLimit)
            ->get();

        // Top up with the nearest hotels when too few are linked to the destination
        if ($accommodations->count() < $accommodationLimit && $destination->latitude !== null && $destination->longitude !== null) {
            $accommodations = $accommodations->concat(Accommodation::nearest(
                (float) $destination->latitude,
                (float) $destination->longitude,
                $accommodationLimit - $accommodations->count(),
                $accommodations->modelKeys(),
                $accommodationRelations,
            ));
        }

        // Get tours for this destination
        $tours = $destination->tours()
            ->with(['images', 'reviews'])
            ->published()
            ->where('is_active', true)
            ->orderBy('clicks_count', 'desc')
            ->limit(6)
            ->get();

        // Restaurants linked to this destination (picker or destination field)...
        $restaurantLimit = 6;
        $restaurantRelations = ['images', 'affiliateNetwork'];
        $restaurants = Restaurant::query()
            ->where(fn ($query) => $query
                ->where('destination_id', $destination->id)
                ->orWhereHas('destinations', fn ($linked) => $linked->whereKey($destination->id)))
            ->with($restaurantRelations)
            ->published()
            ->where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('average_rating', 'desc')
            ->limit($restaurantLimit)
            ->get();

        // ...topped up with the nearest ones, so new restaurants show without linking
        if ($restaurants->count() < $restaurantLimit && $destination->latitude !== null && $destination->longitude !== null) {
            $restaurants = $restaurants->concat(Restaurant::nearest(
                (float) $destination->latitude,
                (float) $destination->longitude,
                $restaurantLimit - $restaurants->count(),
                $restaurants->modelKeys(),
                $restaurantRelations,
            ));
        }

        // Get articles about this destination
        $articles = $destination->articles()
            ->with(['images', 'tags'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get();

        // Get related destinations from the same region
        // The 6 closest other destinations, closest first
        $nearbyDestinations = $destination->nearby();

        // Up to 6 other destinations in the region, picked at random, for the sidebar
        $otherDestinations = Destination::where('region_id', $destination->region_id)
            ->whereKeyNot($destination->getKey())
            ->with(['images', 'region'])
            ->inRandomOrder()
            ->limit(6)
            ->get();

        return view('front.pages.destination', compact(
            'destination',
            'accommodations',
            'tours',
            'restaurants',
            'articles',
            'otherDestinations',
            'nearbyDestinations'
        ));
    }

    /** Most hotels listed on a destination's hotels page. */
    public const HOTELS_PAGE_LIMIT = 50;

    /** Hotels per page on a destination's hotels page. */
    public const HOTELS_PER_PAGE = 12;

    /** Ways to sort the hotels page (`?sort=`). */
    public const HOTEL_SORTS = ['distance' => 'Closest first', 'stars' => 'Most stars'];

    /** Budget groups on the hotels page (`?budget=`), by star range [min, max]. */
    public const HOTEL_BUDGETS = [
        'low' => ['label' => 'Low budget', 'phrase' => 'low-budget', 'hint' => 'Homestays, guesthouses and 1–2★', 'stars' => [null, 2]],
        'medium' => ['label' => 'Medium', 'phrase' => 'mid-range', 'hint' => '3★ hotels', 'stars' => [3, 3]],
        'high' => ['label' => 'High', 'phrase' => 'high-end', 'hint' => '4–5★ hotels and resorts', 'stars' => [4, 5]],
    ];

    /**
     * All hotels near a destination: the ones linked to it plus the closest others, up to 50.
     */
    public function hotels(Request $request, string $regionSlug, string $destinationSlug): View
    {
        $destination = $this->findDestination($regionSlug, $destinationSlug);
        $hasLocation = $destination->latitude !== null && $destination->longitude !== null;
        $relations = ['images', 'affiliateNetwork'];
        $budget = array_key_exists($request->query('budget'), self::HOTEL_BUDGETS) ? $request->query('budget') : null;
        $stars = $budget ? self::HOTEL_BUDGETS[$budget]['stars'] : null;

        $hotels = $destination->accommodations()
            ->with($relations)
            ->published()
            ->where('is_active', true)
            ->when($stars, fn ($query) => $query->starRange(...$stars))
            ->limit(self::HOTELS_PAGE_LIMIT)
            ->get();

        if ($hasLocation) {
            $hotels->each(function (Accommodation $hotel) use ($destination): void {
                if ($hotel->latitude !== null && $hotel->longitude !== null) {
                    $hotel->distance_km = Geo::distanceKm((float) $destination->latitude, (float) $destination->longitude, (float) $hotel->latitude, (float) $hotel->longitude);
                }
            });

            if ($hotels->count() < self::HOTELS_PAGE_LIMIT) {
                $hotels = $hotels->concat(Accommodation::nearest(
                    (float) $destination->latitude,
                    (float) $destination->longitude,
                    self::HOTELS_PAGE_LIMIT - $hotels->count(),
                    $hotels->modelKeys(),
                    $relations,
                    $stars,
                    closestFirst: true,
                ));
            }
        }

        $sort = array_key_exists($request->query('sort'), self::HOTEL_SORTS) ? $request->query('sort') : 'distance';
        $byDistance = fn (Accommodation $a, Accommodation $b): int => ($a->distance_km ?? INF) <=> ($b->distance_km ?? INF);
        $hotels = $hotels->sort($sort === 'stars'
            ? fn (Accommodation $a, Accommodation $b): int => (($b->star_rating ?? 0) <=> ($a->star_rating ?? 0)) ?: $byDistance($a, $b)
            : $byDistance)->values();

        $farthestKm = $hotels->max('distance_km');
        $page = Paginator::resolveCurrentPage();
        $hotels = new LengthAwarePaginator(
            $hotels->forPage($page, self::HOTELS_PER_PAGE)->values(),
            $hotels->count(),
            self::HOTELS_PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => array_filter(['budget' => $budget, 'sort' => $sort === 'distance' ? null : $sort])],
        );

        abort_if($hotels->isEmpty() && $page > 1, 404);

        return view('front.pages.destination-hotels', [
            'destination' => $destination,
            'hotels' => $hotels,
            'farthestKm' => $farthestKm,
            'sort' => $sort,
            'sorts' => self::HOTEL_SORTS,
            'budget' => $budget,
            'budgets' => self::HOTEL_BUDGETS,
        ]);
    }

    protected function findDestination(string $regionSlug, string $destinationSlug): Destination
    {
        return Destination::where('slug', $destinationSlug)
            ->whereHas('region', fn ($query) => $query->where('slug', $regionSlug))
            ->with(['region', 'images'])
            ->firstOrFail();
    }
}
