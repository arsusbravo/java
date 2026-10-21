<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-linear-to-br from-java-primary to-java-dark px-8 py-12 md:px-16 md:py-16 text-white scroll-animate opacity-0">
            <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-java-accent/30 blur-3xl" aria-hidden="true"></div>
            <div class="relative grid gap-8 md:grid-cols-[1fr_auto] md:items-center">
                <div>
                    <span class="inline-block px-4 py-1.5 bg-java-accent rounded-full text-sm font-semibold mb-4">New: Trip Planner</span>
                    <h2 class="font-playfair text-3xl md:text-5xl font-bold mb-4">Your Java trip, planned day by day</h2>
                    <p class="text-lg text-white/80 max-w-2xl">Tell us where you start, how long you have and what you love. Get a route with drive times and a hotel for every night, in under a minute.</p>
                </div>
                <a href="{{ route('trip-planner') }}" class="inline-flex items-center justify-center gap-3 rounded-full bg-java-accent px-8 py-4 font-semibold shadow-2xl transition hover:-translate-y-0.5 hover:bg-orange-600">
                    Plan my trip
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>
