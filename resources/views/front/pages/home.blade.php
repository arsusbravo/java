@extends('front.layout.app')

@section('hero')
<!-- Hero Section with Parallax Effect -->
<section class="hero-parallax relative h-screen flex items-center justify-center overflow-hidden" id="home">
    <div class="parallax-bg absolute inset-0">
        <img src="{{ asset('images/sunrise.png') }}" alt="Java Sunrise" class="w-full h-full object-cover scale-110">
    </div>
    <div class="absolute inset-0 bg-linear-to-b from-black/40 via-black/30 to-black/60"></div>
    
    <!-- Animated overlay patterns -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-20 left-10 w-72 h-72 bg-java-accent rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
        <div class="absolute top-40 right-10 w-72 h-72 bg-orange-400 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-20 left-1/3 w-72 h-72 bg-yellow-400 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
    </div>
    
    <div class="hero-content relative z-10 text-center text-white max-w-5xl px-6 lg:px-8">
        <div class="scroll-animate opacity-0">
            <span class="inline-block px-4 py-2 bg-java-accent/90 backdrop-blur-sm rounded-full text-sm font-semibold mb-6 animate-bounce-slow">
                🌅 Welcome to Paradise
            </span>
            <h1 class="font-playfair text-5xl md:text-7xl lg:text-8xl font-black mb-8 leading-tight">
                Discover the <span class="text-java-accent">Magic</span><br>of Java Island
            </h1>
            <p class="text-xl md:text-2xl mb-12 font-light max-w-3xl mx-auto leading-relaxed">
                Embark on an unforgettable journey through ancient temples, pristine beaches, volcanic landscapes, and vibrant cultural traditions
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="#destinations" class="cta-button group inline-flex items-center gap-3 px-10 py-5 bg-java-accent hover:bg-java-accent/90 text-white rounded-full font-semibold transition-all duration-300 hover:-translate-y-1 shadow-2xl hover:shadow-java-accent/50">
                    <span>Explore Destinations</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
                <a href="#articles" class="inline-flex items-center gap-2 px-10 py-5 bg-white/10 backdrop-blur-md hover:bg-white/20 text-white rounded-full font-semibold transition-all duration-300 border-2 border-white/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Watch Video</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
        <div class="w-6 h-10 border-2 border-white/50 rounded-full flex justify-center">
            <div class="w-1.5 h-3 bg-white/80 rounded-full mt-2 animate-scroll"></div>
        </div>
    </div>
</section>
@endsection

@section('content')

<!-- Stats Section -->
@include('front.pages.sections.statistics')

<!-- Featured Destinations -->
@include('front.pages.sections.features')

<!-- Latest Articles - Magazine Style -->
@include('front.pages.sections.lastArticles')

<!-- Testimonials -->
@include('front.pages.sections.testimonials')

<!-- Newsletter Section -->
@include('front.pages.sections.newsletter')

@endsection