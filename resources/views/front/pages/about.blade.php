@extends('front.layout.app')

@section('hero')
<!-- About Hero Section -->
<section class="hero-parallax relative h-[70vh] flex items-center justify-center overflow-hidden">
    <div class="parallax-bg absolute inset-0">
        <img src="{{ image_url('images/about-hero.jpeg', 'hero') }}" alt="About JavaSunrise" class="w-full h-full object-cover scale-110">
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
                🌏 Our Mission
            </span>
            <h1 class="font-playfair text-5xl md:text-7xl lg:text-8xl font-black mb-8 leading-tight">
                About <span class="text-java-accent">JavaSunrise</span>
            </h1>
            <p class="text-xl md:text-2xl font-light max-w-3xl mx-auto leading-relaxed">
                Inspiring travelers to discover the hidden gems of Java Island
            </p>
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

<!-- Our Story Section -->
<section class="py-16 lg:py-24 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-16 scroll-animate opacity-0 translate-y-8">
                <h2 class="font-playfair text-4xl md:text-5xl font-black text-gray-900 mb-6">
                    Our Story
                </h2>
                <div class="w-24 h-1 bg-java-accent mx-auto"></div>
            </div>

            <div class="prose prose-lg max-w-none scroll-animate opacity-0 translate-y-8">
                <p class="text-xl text-gray-700 leading-relaxed mb-6">
                    JavaSunrise is a travel blog dedicated to showcasing the incredible beauty, rich culture, and diverse experiences that Java Island has to offer. Our mission is simple: to inspire travelers from around the world to explore Indonesia and support the growth of tourism in this magnificent country.
                </p>

                <p class="text-lg text-gray-600 leading-relaxed mb-6">
                    Indonesia is home to some of the world's most breathtaking landscapes, from the volcanic peaks of Mount Bromo to the ancient temples of Borobudur and Prambanan. Yet, many of these treasures remain undiscovered by international travelers. We believe that by sharing authentic stories, detailed guides, and insider tips, we can help more people experience the magic of Java Island.
                </p>

                <p class="text-lg text-gray-600 leading-relaxed mb-6">
                    Through our blog, we aim to support local communities, promote sustainable tourism, and contribute to the economic development of Indonesia's tourism industry. Every destination we feature, every guide we write, and every story we share is crafted with the goal of highlighting what makes Java truly special.
                </p>

                <p class="text-lg text-gray-600 leading-relaxed">
                    Whether you're planning your first trip to Java or you're a seasoned traveler looking for new adventures, JavaSunrise is here to guide you every step of the way. Join us as we explore pristine beaches, vibrant cities, lush rice terraces, and everything in between.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="py-16 lg:py-24 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-12 max-w-6xl mx-auto">
            <!-- Mission -->
            <div class="scroll-animate opacity-0 translate-y-8">
                <div class="bg-white rounded-2xl p-8 lg:p-12 shadow-lg h-full">
                    <div class="w-16 h-16 bg-java-accent/10 rounded-2xl flex items-center justify-center mb-6">
                        <span class="text-4xl">🎯</span>
                    </div>
                    <h3 class="font-playfair text-3xl font-bold text-gray-900 mb-6">
                        Our Mission
                    </h3>
                    <p class="text-gray-600 leading-relaxed">
                        To promote Indonesia's tourism industry by providing comprehensive, authentic, and inspiring travel content that encourages global travelers to explore Java Island. We strive to support local communities and businesses while fostering sustainable tourism practices.
                    </p>
                </div>
            </div>

            <!-- Vision -->
            <div class="scroll-animate opacity-0 translate-y-8">
                <div class="bg-white rounded-2xl p-8 lg:p-12 shadow-lg h-full">
                    <div class="w-16 h-16 bg-java-accent/10 rounded-2xl flex items-center justify-center mb-6">
                        <span class="text-4xl">🌟</span>
                    </div>
                    <h3 class="font-playfair text-3xl font-bold text-gray-900 mb-6">
                        Our Vision
                    </h3>
                    <p class="text-gray-600 leading-relaxed">
                        To become the leading resource for travelers seeking authentic experiences in Java Island, while contributing to the growth and sustainability of Indonesia's tourism sector. We envision a future where Java's hidden gems are celebrated worldwide.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What We Offer -->
<section class="py-16 lg:py-24 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 scroll-animate opacity-0 translate-y-8">
            <h2 class="font-playfair text-4xl md:text-5xl font-black text-gray-900 mb-6">
                What We Offer
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Everything you need to plan your perfect Java adventure
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <!-- Feature 1 -->
            <div class="text-center scroll-animate opacity-0 translate-y-8">
                <div class="w-20 h-20 bg-linear-to-br from-java-accent to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <span class="text-4xl">📚</span>
                </div>
                <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-4">
                    Travel Guides
                </h3>
                <p class="text-gray-600 leading-relaxed">
                    Comprehensive guides covering destinations, activities, accommodations, and local insights to help you make the most of your Java adventure.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="text-center scroll-animate opacity-0 translate-y-8">
                <div class="w-20 h-20 bg-linear-to-br from-java-primary to-blue-900 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <span class="text-4xl">🗺️</span>
                </div>
                <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-4">
                    Itineraries
                </h3>
                <p class="text-gray-600 leading-relaxed">
                    Ready-to-use travel itineraries designed for different interests, budgets, and timeframes to simplify your trip planning.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="text-center scroll-animate opacity-0 translate-y-8">
                <div class="w-20 h-20 bg-linear-to-br from-java-accent to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <span class="text-4xl">💡</span>
                </div>
                <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-4">
                    Travel Tips
                </h3>
                <p class="text-gray-600 leading-relaxed">
                    Practical advice, insider tips, and local knowledge to help you travel smarter and experience Java like a local.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Contact CTA -->
<section class="py-20 bg-linear-to-br from-java-primary via-blue-900 to-java-primary relative overflow-hidden" id="contact">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-java-accent rounded-full blur-3xl animate-blob"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-blue-500 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    </div>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-3xl mx-auto text-center scroll-animate opacity-0 translate-y-8">
            <h2 class="font-playfair text-3xl md:text-5xl font-black text-white mb-6">
                Have Questions or Suggestions?
            </h2>
            <p class="text-xl text-blue-100 mb-10 font-light leading-relaxed">
                We'd love to hear from you! Whether you have questions about Java Island, want to share your travel story, or have suggestions for our blog, feel free to reach out.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="mailto:hello@javasunrise.com" 
                   class="cta-button group inline-flex items-center gap-3 px-10 py-5 bg-java-accent hover:bg-orange-600 text-white rounded-full font-bold text-lg shadow-2xl shadow-java-accent/30 transition-all duration-300 hover:-translate-y-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>Contact Us</span>
                </a>
                <a href="{{ route('articles.plan') }}" 
                   class="inline-flex items-center gap-2 px-10 py-5 bg-white/10 backdrop-blur-md hover:bg-white/20 text-white rounded-full font-bold text-lg transition-all duration-300 border-2 border-white/30">
                    <span>Start Planning</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// Scroll animations
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('opacity-100', 'translate-y-0');
                entry.target.classList.remove('opacity-0', 'translate-y-8');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.scroll-animate').forEach((el) => observer.observe(el));
});
</script>

@endsection