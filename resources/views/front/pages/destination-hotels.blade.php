@extends('front.layout.app')

@section('title', "Hotels near {$destination->name}, {$destination->region->name} | Java Sunrise")
@section('meta_description', "Compare " . ($hotels->total() ?: 'the') . " hotels, resorts and guesthouses near {$destination->name}, sorted by distance, with star ratings and prices from our booking partners.")

@php
    $budgetLabel = $budget ? $budgets[$budget]['phrase'] : null;
    // Link to this page with changed filters; defaults are left out of the URL
    $hotelsUrl = fn (array $changes) => route('destinations.hotels', [
        $destination->region->slug,
        $destination->slug,
        ...array_filter([...['budget' => $budget, 'sort' => $sort === 'distance' ? null : $sort], ...$changes]),
    ]);
@endphp

@section('content')
<!-- Hero Section -->
<div class="relative h-80 bg-cover bg-center" style="background-image: url('{{ $destination->featured_image_url }}');">
    <div class="absolute inset-0 bg-black/55"></div>
    <div class="relative container mx-auto px-4 h-full flex items-end pb-12">
        <div class="text-white">
            <nav class="text-sm mb-4" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-blue-300">Home</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('destinations.region', $destination->region->slug) }}" class="hover:text-blue-300">{{ $destination->region->name }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('destinations.show', [$destination->region->slug, $destination->slug]) }}" class="hover:text-blue-300">{{ $destination->name }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-blue-300" aria-current="page">Hotels</li>
                </ol>
            </nav>
            <h1 class="text-4xl md:text-5xl font-bold">Hotels near {{ $destination->name }}</h1>
            @if($hotels->total() > 0)
                <p class="mt-3 text-lg text-white/90">
                    The {{ $hotels->total() }} closest {{ $budgetLabel ? $budgetLabel.' ' : '' }}places to stay{{ $farthestKm ? ', all within '.distance_label($farthestKm) : '' }}
                </p>
            @endif
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    @if($hotels->total() === 0 && ! $budget)
        <div class="rounded-2xl bg-gray-50 p-10 text-center">
            <p class="text-lg text-gray-700">We don't have partner hotels near {{ $destination->name }} yet.</p>
            <a href="{{ route('destinations.show', [$destination->region->slug, $destination->slug]) }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800">← Back to {{ $destination->name }}</a>
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-center gap-x-6 gap-y-2">
            <a href="{{ route('destinations.show', [$destination->region->slug, $destination->slug]) }}" class="text-blue-600 hover:text-blue-800">← Back to {{ $destination->name }}</a>
            @if($hotels->total() > 0)
                <span class="text-sm text-gray-500">Showing {{ $hotels->firstItem() }}–{{ $hotels->lastItem() }} of {{ $hotels->total() }}</span>
            @endif
        </div>

        <div class="mb-8 flex flex-col gap-4 rounded-2xl bg-gray-50 p-4 lg:flex-row lg:items-center lg:justify-between">
            <nav class="flex flex-wrap gap-2" aria-label="Filter by budget">
                <a href="{{ $hotelsUrl(['budget' => null]) }}"
                   @if(! $budget) aria-current="true" @endif
                   class="rounded-full px-4 py-2 text-sm font-medium {{ ! $budget ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 shadow-sm hover:bg-gray-100' }}">All budgets</a>
                @foreach($budgets as $key => $option)
                    <a href="{{ $hotelsUrl(['budget' => $key]) }}" title="{{ $option['hint'] }}"
                       @if($budget === $key) aria-current="true" @endif
                       class="rounded-full px-4 py-2 text-sm font-medium {{ $budget === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 shadow-sm hover:bg-gray-100' }}">
                        {{ $option['label'] }} <span class="{{ $budget === $key ? 'text-white/80' : 'text-gray-500' }}">· {{ $option['hint'] }}</span>
                    </a>
                @endforeach
            </nav>
            <nav class="flex shrink-0 gap-2" aria-label="Sort hotels">
                @foreach($sorts as $key => $label)
                    <a href="{{ $hotelsUrl(['sort' => $key === 'distance' ? null : $key]) }}"
                       @if($sort === $key) aria-current="true" @endif
                       class="rounded-full px-4 py-2 text-sm font-medium {{ $sort === $key ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 shadow-sm hover:bg-gray-100' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        @if($hotels->total() === 0)
            <div class="rounded-2xl border border-gray-200 p-10 text-center">
                <p class="text-lg text-gray-700">No {{ $budgetLabel }} hotels near {{ $destination->name }} yet.</p>
                <a href="{{ $hotelsUrl(['budget' => null]) }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800">Show all budgets</a>
            </div>
        @endif

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($hotels as $accommodation)
                @include('front.partials.hotel-card', ['from' => 'destination'])
            @endforeach
        </div>

        @if($hotels->hasPages())
            <div class="mt-10">
                {{ $hotels->onEachSide(1)->links('front.partials.pagination') }}
            </div>
        @endif

        @include('front.partials.affiliate-note', ['class' => 'mt-10 text-center'])
    @endif
</div>
@endsection
