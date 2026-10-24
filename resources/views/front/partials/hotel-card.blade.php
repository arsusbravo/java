{{--
    Hotel card with tracked booking link.
    Needs $accommodation and $from (an AffiliateClick source). Optional $stay (App\Support\Agoda\Stay):
    then $accommodation->offer holds the live price for those dates, or null when there are no rooms.
--}}
@php
    $stay ??= null;
    $offer = $stay ? $accommodation->offer : null;
    $price = $stay ? $offer?->dailyRate : $accommodation->price_from;
    $currency = $offer?->currency ?? $accommodation->currency;
    $crossedOut = $stay ? $offer?->crossedOutRate : $accommodation->crossed_out_price;
    $discount = $stay ? $offer?->discountPercent : $accommodation->discount_percent;
    $score = $offer?->reviewScore ?? $accommodation->review_score;
    $reviews = $offer?->reviewCount ?? $accommodation->review_count;
    $breakfast = $offer?->breakfast ?? $accommodation->includes_breakfast;
    $wifi = $offer?->wifi ?? $accommodation->free_wifi;
@endphp
<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow flex flex-col">
    <a href="{{ $accommodation->url() }}{{ $stay ? '?'.http_build_query($stay->query()) : '' }}" class="relative block h-48 bg-cover bg-center" style="background-image: url('{{ $accommodation->featured_image_url }}');" aria-label="{{ $accommodation->name }}" tabindex="-1">
        @isset($accommodation->distance_km)
            <div class="absolute top-4 left-4 bg-white/90 text-gray-800 px-3 py-1 rounded-full text-sm font-medium shadow-sm">
                {{ distance_label($accommodation->distance_km) }} away
            </div>
        @endisset
        <div class="absolute top-4 right-4 bg-blue-600 text-white px-3 py-1 rounded-full text-sm">
            {{ \App\Models\Accommodation::TYPES[$accommodation->type] ?? ucfirst($accommodation->type) }}
        </div>
        @if($price && $discount)
            <div class="absolute bottom-4 left-4 bg-red-600 text-white px-3 py-1 rounded-full text-sm font-semibold">−{{ $discount }}%</div>
        @endif
    </a>
    <div class="p-4 flex flex-1 flex-col">
        <h3 class="font-bold text-lg mb-2"><a href="{{ $accommodation->url() }}{{ $stay ? '?'.http_build_query($stay->query()) : '' }}" class="hover:text-blue-600">{{ $accommodation->name }}</a></h3>
        <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1">
            @if($accommodation->star_rating)
                <div class="flex items-center text-yellow-400" aria-label="{{ $accommodation->star_rating }}-star property">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-4 h-4 fill-current {{ $i < $accommodation->star_rating ? '' : 'text-gray-300' }}" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                        </svg>
                    @endfor
                </div>
            @endif
            @if($score)
                <div class="flex items-center gap-1.5 text-sm" title="Agoda guest rating">
                    <span class="rounded bg-blue-900 px-1.5 py-0.5 font-semibold text-white">{{ number_format((float) $score, 1) }}</span>
                    @if(rating_label($score))<span class="font-medium text-gray-800">{{ rating_label($score) }}</span>@endif
                    @if($reviews)<span class="text-gray-500">· {{ number_format($reviews) }} {{ \Illuminate\Support\Str::plural('review', $reviews) }}</span>@endif
                </div>
            @endif
        </div>
        @if($breakfast || $wifi)
            <div class="mb-2 flex flex-wrap gap-2 text-xs">
                @if($breakfast)<span class="rounded-full bg-green-50 px-2 py-0.5 font-medium text-green-700">Breakfast included</span>@endif
                @if($wifi)<span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700">Free Wi-Fi</span>@endif
            </div>
        @endif
        <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $accommodation->short_description ?? strip_tags($accommodation->description) }}</p>
        <div class="mt-auto flex justify-between items-end gap-3">
            @if($price)
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-blue-600 font-bold">{{ $stay ? '' : 'From ' }}{{ money($price, $currency) }}<span class="text-sm font-normal text-gray-600"> / night</span></span>
                        @if($crossedOut)<span class="text-sm text-gray-400 line-through">{{ money($crossedOut, $currency) }}</span>@endif
                    </div>
                    @if($stay || $accommodation->prices_checked_at)
                        <p class="text-xs text-gray-500">
                            excl. taxes · {{ $stay ? 'for your dates' : 'checked '.$accommodation->prices_checked_at->format('j M') }}
                        </p>
                    @endif
                </div>
            @elseif($stay)
                <span class="text-sm font-medium text-gray-500">No rooms for your dates</span>
            @else
                <span class="text-sm text-gray-500">Check prices on {{ $accommodation->affiliateNetwork?->name ?? 'partner site' }}</span>
            @endif
            <div class="flex shrink-0 gap-2">
                <a href="{{ $accommodation->url() }}{{ $stay ? '?'.http_build_query($stay->query()) : '' }}" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:border-blue-600 hover:text-blue-600">Details</a>
                @if($accommodation->affiliate_url)
                    <a href="{{ booking_url($accommodation, $from, $stay) }}" target="_blank" rel="nofollow sponsored noopener" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm whitespace-nowrap">
                        {{ $stay && ! $price ? 'Other dates' : 'Book Now' }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
