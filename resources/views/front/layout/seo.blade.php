{{--
    Search and sharing tags for every public page. Pages set them with sections:
    title, meta_description, canonical, robots, og_type, og_image; and push JSON-LD to the
    'structured_data' stack (see json_ld() / breadcrumb_ld() in app/helpers.php).
--}}
@php
    // Sections arrive HTML-escaped; decode them once so they aren't escaped twice below
    $section = fn (string $name): string => trim(html_entity_decode($__env->yieldContent($name), ENT_QUOTES | ENT_HTML5));
    $page = max(1, (int) request()->query('page', 1));
    $seoTitle = $section('title') ?: 'Java Sunrise – Discover the Beauty of Java Island';
    // Titles typed in the admin ("Borobudur Temple") get the site name when there is room
    if (! str_contains($seoTitle, 'Java Sunrise') && mb_strlen($seoTitle) <= 50) {
        $seoTitle .= ' | Java Sunrise';
    }
    // Page 2+ of a list gets its own title, so Google doesn't see duplicates
    if ($page > 1) {
        $seoTitle = preg_replace('/( \| Java Sunrise)?$/', " – Page {$page}$1", $seoTitle, 1);
    }
    $seoDescription = $section('meta_description') ?: 'Discover Java Island: travel guides, destinations, hotels, tours and local food across West, Central and East Java.';
    // The page without filters, sorting or dates: those are views of the same page
    $canonical = $section('canonical') ?: url()->current().($page > 1 ? '?page='.$page : '');
    $hasFilters = collect(request()->query())->except('page')->isNotEmpty();
    $robots = $section('robots') ?: ($hasFilters ? 'noindex, follow' : 'index, follow, max-image-preview:large');
    $ogImage = $section('og_image');
    if ($ogImage === '' || str_ends_with(parse_url($ogImage, PHP_URL_PATH) ?? '', '.svg')) {
        $ogImage = asset('images/sunrise.png');
    }
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ $robots }}">
@if(config('services.google.site_verification'))
<meta name="google-site-verification" content="{{ config('services.google.site_verification') }}">
@endif

<meta property="og:site_name" content="Java Sunrise">
<meta property="og:locale" content="en_US">
<meta property="og:type" content="{{ $section('og_type') ?: 'website' }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@stack('structured_data')
