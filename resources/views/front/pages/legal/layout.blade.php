@extends('front.layout.app')

@section('content')
<header class="bg-linear-to-br from-java-primary to-java-dark text-white pt-36 pb-16">
    <div class="max-w-3xl mx-auto px-6">
        <h1 class="font-playfair text-4xl md:text-5xl font-bold">@yield('heading')</h1>
        @hasSection('subheading')
            <p class="mt-4 text-white/80">@yield('subheading')</p>
        @endif
    </div>
</header>

<article class="max-w-3xl mx-auto px-6 py-16 text-gray-700 leading-relaxed space-y-5
    [&_h2]:font-playfair [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-gray-900 [&_h2]:pt-6
    [&_h3]:font-semibold [&_h3]:text-gray-900 [&_h3]:pt-2
    [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:space-y-2 [&_a]:text-java-primary [&_a]:underline">
    @yield('body')
</article>
@endsection
