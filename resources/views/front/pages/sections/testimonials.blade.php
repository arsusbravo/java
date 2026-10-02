<section class="py-24 bg-white" id="testimonials">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16 scroll-animate opacity-0">
            <span class="inline-block px-4 py-2 bg-java-accent/10 text-java-accent rounded-full text-sm font-semibold mb-4">
                Traveler Stories
            </span>
            <h2 class="font-playfair text-4xl md:text-6xl font-bold text-java-primary mb-6">
                What Travelers Say
            </h2>
        </div>

        @if($homeFeaturedReviews->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($homeFeaturedReviews as $review)
            <div class="testimonial-card bg-gray-50 rounded-3xl p-8 scroll-animate opacity-0 hover:shadow-xl transition-shadow duration-300">
                <!-- Star Rating -->
                <div class="flex gap-1 mb-4">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }} fill-current" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    @endfor
                </div>

                <!-- Review Comment -->
                <p class="text-gray-700 leading-relaxed mb-6 italic">
                    "{{ $review->comment }}"
                </p>

                <!-- Reviewer Info -->
                <div class="flex items-center gap-4">
                    @php
                        $nameParts = explode(' ', $review->user_name);
                        $initials = '';
                        foreach($nameParts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                    @endphp
                    <div class="w-12 h-12 bg-java-accent rounded-full flex items-center justify-center text-white font-bold">
                        {{ $initials }}
                    </div>
                    <div>
                        <p class="font-semibold text-java-primary">{{ $review->user_name }}</p>
                        @if($review->reviewable)
                            <p class="text-sm text-gray-500">Visited {{ $review->reviewable->name }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>