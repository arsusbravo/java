{{-- Required disclosure next to booking links --}}
<p class="{{ $class ?? 'mt-6 text-center' }} text-xs text-gray-500">
    We may earn a commission when you book through these links, at no extra cost to you.
    <a href="{{ route('affiliate-disclosure') }}" class="underline hover:text-gray-700">How this works</a>
</p>
