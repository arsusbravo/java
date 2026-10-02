<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Region;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    /**
     * Display a listing of all destinations.
     */
    public function index(Request $request)
    {
        // Get all regions for filter dropdown
        $regions = Region::with(['destinations' => function($query) use ($request) {
            // Apply search filter
            if ($request->filled('search')) {
                $query->where(function($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }
            $query->orderBy('order')->orderBy('name');
        }])->ordered()->get();

        // Build search query for featured destinations
        $featuredQuery = Destination::with('region');

        // Apply filters
        if ($request->filled('search')) {
            $featuredQuery->where(function($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('region')) {
            $featuredQuery->whereHas('region', function($query) use ($request) {
                $query->where('slug', $request->region);
            });
        }

        // Without filters show the featured selection; with filters show every match
        $featuredQuery->orderBy('order');
        if (!$request->filled('search') && !$request->filled('region')) {
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
        $destination = Destination::where('slug', $destinationSlug)
            ->whereHas('region', function($query) use ($regionSlug) {
                $query->where('slug', $regionSlug);
            })
            ->with(['region', 'images'])
            ->firstOrFail();

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

        // Get restaurants for this destination
        $restaurants = $destination->restaurants()
            ->with(['images', 'reviews'])
            ->published()
            ->where('is_active', true)
            ->orderBy('clicks_count', 'desc')
            ->limit(6)
            ->get();

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
}