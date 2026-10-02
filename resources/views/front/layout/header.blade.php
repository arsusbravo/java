<nav id="navbar" class="fixed top-0 left-0 right-0 bg-white/95 backdrop-blur-lg z-50 shadow-sm transition-all duration-300">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <!-- Logo -->
            <a href="{{ url('/') }}" class="logo flex items-center gap-3 font-playfair text-2xl lg:text-3xl font-bold text-java-primary hover:text-java-accent transition-colors duration-300">
                <img src="{{ asset('images/logo-sm.png') }}" alt="JavaSunrise Logo" class="h-10 lg:h-12 w-auto object-contain">
                <span>Java<span class="text-java-accent">Sunrise</span></span>
            </a>
            
            <!-- Desktop Navigation -->
            <ul class="hidden md:flex gap-8 list-none items-center">
                <li><a href="{{ url('/') }}" class="nav-link font-medium text-gray-700 transition-colors duration-300 relative text-sm uppercase tracking-wide{{ request()->is('/') ? ' ' : ' hover:' }}text-java-accent">Home</a></li>
                <li><a href="{{ url('destinations') }}" class="nav-link font-medium text-gray-700 transition-colors duration-300 relative text-sm uppercase tracking-wide{{ request()->is('destinations*') ? ' ' : ' hover:' }}text-java-accent">Destinations</a></li>
                <li><a href="{{ url('plan') }}" class="nav-link font-medium text-gray-700 transition-colors duration-300 relative text-sm uppercase tracking-wide{{ request()->is('plan*') ? ' ' : ' hover:' }}text-java-accent">Plan your trip</a></li>
                <li><a href="{{ url('guides') }}" class="nav-link font-medium text-gray-700 transition-colors duration-300 relative text-sm uppercase tracking-wide{{ request()->is('guides*') ? ' ' : ' hover:' }}text-java-accent">Guides</a></li>
                <li><a href="{{ url('about') }}" class="nav-link font-medium text-gray-700 transition-colors duration-300 relative text-sm uppercase tracking-wide{{ request()->is('about*') ? ' ' : ' hover:' }}text-java-accent">About</a></li>
            </ul>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden text-gray-700 hover:text-java-accent transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden md:hidden pb-4">
            <ul class="flex flex-col gap-4">
                <li><a href="{{ url('/') }}" class="block font-medium text-gray-700 hover:text-java-accent transition-colors duration-300 text-sm uppercase tracking-wide">Home</a></li>
                <li><a href="#destinations" class="block font-medium text-gray-700 hover:text-java-accent transition-colors duration-300 text-sm uppercase tracking-wide">Destinations</a></li>
                <li><a href="#articles" class="block font-medium text-gray-700 hover:text-java-accent transition-colors duration-300 text-sm uppercase tracking-wide">Articles</a></li>
                <li><a href="#about" class="block font-medium text-gray-700 hover:text-java-accent transition-colors duration-300 text-sm uppercase tracking-wide">About</a></li>
                <li><a href="#contact" class="block font-medium text-gray-700 hover:text-java-accent transition-colors duration-300 text-sm uppercase tracking-wide">Contact</a></li>
            </ul>
        </div>
    </div>
</nav>