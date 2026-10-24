{{-- Page buttons for the public site: $paginator->links('front.partials.pagination') --}}
@if($paginator->hasPages())
<nav class="flex flex-col items-center gap-3" aria-label="Pages">
    <ul class="flex flex-wrap items-center justify-center gap-2">
        <li>
            @if($paginator->onFirstPage())
                <span class="inline-flex h-11 items-center rounded-full border border-gray-200 px-5 text-gray-400" aria-disabled="true">← Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-11 items-center rounded-full border border-gray-300 px-5 font-medium text-gray-700 hover:border-blue-600 hover:text-blue-600">← Previous</a>
            @endif
        </li>

        @foreach($elements as $element)
            @if(is_string($element))
                <li class="hidden sm:block"><span class="inline-flex h-11 w-11 items-center justify-center text-gray-400">…</span></li>
            @endif

            @if(is_array($element))
                @foreach($element as $page => $url)
                    <li class="hidden sm:block">
                        @if($page == $paginator->currentPage())
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-blue-600 font-semibold text-white" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-gray-300 font-medium text-gray-700 hover:border-blue-600 hover:text-blue-600" aria-label="Page {{ $page }}">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach
            @endif
        @endforeach

        <li>
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-11 items-center rounded-full border border-gray-300 px-5 font-medium text-gray-700 hover:border-blue-600 hover:text-blue-600">Next →</a>
            @else
                <span class="inline-flex h-11 items-center rounded-full border border-gray-200 px-5 text-gray-400" aria-disabled="true">Next →</span>
            @endif
        </li>
    </ul>
    <p class="text-sm text-gray-500">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</p>
</nav>
@endif
