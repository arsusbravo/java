{{-- Hotel card with tracked booking link. Needs $accommodation and $from (an AffiliateClick source). --}}
<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
    <div class="relative h-48 bg-cover bg-center" style="background-image: url('{{ $accommodation->featured_image_url }}');">
        @isset($accommodation->distance_km)
            <div class="absolute top-4 left-4 bg-white/90 text-gray-800 px-3 py-1 rounded-full text-sm font-medium shadow-sm">
                {{ distance_label($accommodation->distance_km) }} away
            </div>
        @endisset
        <div class="absolute top-4 right-4 bg-blue-600 text-white px-3 py-1 rounded-full text-sm">
            {{ \App\Models\Accommodation::TYPES[$accommodation->type] ?? ucfirst($accommodation->type) }}
        </div>
    </div>
    <div class="p-4">
        <h3 class="font-bold text-lg mb-2">{{ $accommodation->name }}</h3>
        @if($accommodation->star_rating)
            <div class="flex items-center mb-2 text-yellow-400" aria-label="{{ $accommodation->star_rating }}-star property">
                @for($i = 0; $i < 5; $i++)
                    <svg class="w-4 h-4 fill-current {{ $i < $accommodation->star_rating ? '' : 'text-gray-300' }}" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                    </svg>
                @endfor
            </div>
        @endif
        <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $accommodation->short_description ?? strip_tags($accommodation->description) }}</p>
        <div class="flex justify-between items-center gap-3">
            @if($accommodation->price_from)
                <span class="text-blue-600 font-bold">From {{ $accommodation->currency }} {{ number_format($accommodation->price_from, 0) }}/night</span>
            @else
                <span class="text-sm text-gray-500">Check prices on {{ $accommodation->affiliateNetwork?->name ?? 'partner site' }}</span>
            @endif
            @if($accommodation->affiliate_url)
                <a href="{{ route('go', ['hotel', $accommodation->id, 'from' => $from]) }}" target="_blank" rel="nofollow sponsored noopener" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm whitespace-nowrap">
                    Book Now
                </a>
            @endif
        </div>
    </div>
</div>
