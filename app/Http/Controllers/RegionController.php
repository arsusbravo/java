<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Display the specified region with its destinations.
     */
    public function show($slug)
    {
        $region = Region::where('slug', $slug)
            ->with(['destinations' => function($query) {
                $query->orderBy('is_featured', 'desc')
                      ->orderBy('order')
                      ->orderBy('name');
            }])
            ->firstOrFail();

        // Get featured destinations in this region
        $featuredDestinations = $region->destinations
            ->where('is_featured', true)
            ->take(3);

        // Get other regions
        $otherRegions = Region::where('id', '!=', $region->id)
            ->withCount('destinations')
            ->ordered()
            ->get();

        return view('front.pages.region', compact('region', 'featuredDestinations', 'otherRegions'));
    }
}