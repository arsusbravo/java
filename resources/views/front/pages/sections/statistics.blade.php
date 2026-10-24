@php
    // Real counts only; anything still at zero is left out
    $statItems = array_filter([
        'Java Destinations' => $stats['destinations'],
        'Partner Hotels' => $stats['accommodations'],
        'Travel Guides' => $stats['articles'],
        'Tours' => $stats['tours'],
    ]);
@endphp
@if($statItems)
<!-- Stats Section -->
<section class="py-20 bg-linear-to-br from-java-primary to-java-dark text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-96 h-96 bg-java-accent rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="flex flex-wrap justify-center gap-x-16 gap-y-8 text-center">
            @foreach($statItems as $label => $count)
            <div class="scroll-animate opacity-0 min-w-32">
                <div class="text-5xl font-bold text-java-accent mb-2">{{ number_format($count) }}</div>
                <div class="text-white/80">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
