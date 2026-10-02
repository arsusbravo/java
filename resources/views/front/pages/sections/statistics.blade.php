<!-- Stats Section -->
<section class="py-20 bg-linear-to-br from-java-primary to-java-dark text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-96 h-96 bg-java-accent rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div class="scroll-animate opacity-0">
                <div class="text-5xl font-bold text-java-accent mb-2">{{ $stats['destinations'] }}</div>
                <div class="text-white/80">Java Destinations</div>
            </div>
            <div class="scroll-animate opacity-0">
                <div class="text-5xl font-bold text-java-accent mb-2">{{ $stats['accommodations'] }}+</div>
                <div class="text-white/80">Handpicked Hotels</div>
            </div>
            <div class="scroll-animate opacity-0">
                <div class="text-5xl font-bold text-java-accent mb-2">{{ $stats['tours'] }}+</div>
                <div class="text-white/80">Curated Tours</div>
            </div>
            <div class="scroll-animate opacity-0">
                <div class="text-5xl font-bold text-java-accent mb-2">{{ $stats['reviews'] }}</div>
                <div class="text-white/80">Verified Reviews</div>
            </div>
        </div>
    </div>
</section>