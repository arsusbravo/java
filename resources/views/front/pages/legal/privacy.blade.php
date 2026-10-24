@extends('front.pages.legal.layout')

@section('title', 'Privacy Policy | Java Sunrise')
@section('meta_description', 'What JavaSunrise.com collects when you visit, why, how long it is kept and what your rights are.')
@section('heading', 'Privacy Policy')
@section('subheading', 'Last updated ' . \Illuminate\Support\Carbon::parse(config('site.privacy_updated'))->format('j F Y'))

@section('body')
<p>
    This policy explains what happens to your data when you visit {{ request()->getHost() }}.
    In short: we don't use analytics or advertising trackers, we don't sell data, and we keep as little as we can.
</p>

<h2>Who is responsible</h2>
<p>
    This website is run by {{ config('site.owner') }}@if(config('site.address')), {{ config('site.address') }}@endif.
    For any privacy question or request, use our <a href="{{ route('contact') }}">contact form</a>.
</p>

<h2>What we collect and why</h2>

<h3>Cookies that keep the site working</h3>
<p>We set two cookies. Both are needed for the site to work and do not track you across other websites:</p>
<ul>
    <li><strong>{{ config('session.cookie') }}</strong>: a session cookie. On our server it is linked to your IP address and browser type, which helps protect the site against abuse. It expires after {{ config('session.lifetime') }} minutes without activity, and the session data is then deleted.</li>
    <li><strong>XSRF-TOKEN</strong>: a security token that protects forms against forged requests. It expires together with the session.</li>
</ul>

<h3>Clicks on booking links</h3>
<p>
    When you click a booking link (for example "Book Now" or "Check prices"), we record that the link was clicked,
    so we can see which hotels, tours and restaurants are useful to visitors. We store:
</p>
<ul>
    <li>which listing was clicked and on which part of our site (for example a destination page or the trip planner);</li>
    <li>the address of the page you were on (without anything after the "?");</li>
    <li>your browser type (user agent) and the date and time;</li>
    <li>a <strong>shortened IP address</strong>: the last part is removed (for example 203.0.113.<strong>0</strong>), so it can no longer be traced to your connection.</li>
</ul>
<p>
    We use these records only for our own statistics, never to build a profile of you.
    The legal basis is our legitimate interest in knowing which content is useful (Article 6(1)(f) GDPR).
</p>

<h3>Messages you send us</h3>
<p>
    If you use our <a href="{{ route('contact') }}">contact form</a>, we store your name, email address and message
    so we can read and answer it. We use them only to reply to you, and delete them when they are no longer needed.
    The form is protected against spam by limiting how many messages can be sent in a short time.
</p>

<h3>Server logs</h3>
<p>
    Like every website, our hosting server keeps technical logs (IP address, page requested, time, browser type)
    to keep the site secure and to fix errors. These logs are deleted automatically by the hosting provider.
</p>

<h2>Other companies involved</h2>
<ul>
    <li>
        <strong>Booking partners ({{ \App\Models\AffiliateNetwork::where('is_active', true)->orderBy('name')->pluck('name')->join(', ') ?: 'our affiliate partners' }}).</strong>
        When you click a booking link you leave our site. The partner's own privacy policy and cookies apply there,
        and the partner uses a cookie or link code to see that you came from us. We do not receive your name,
        contact details or payment details. See our <a href="{{ route('affiliate-disclosure') }}">affiliate disclosure</a>.
    </li>
    <li>
        <strong>Google Fonts.</strong> Our fonts are loaded from Google's servers, which means your browser sends your
        IP address to Google. See <a href="https://policies.google.com/privacy" rel="noopener" target="_blank">Google's privacy policy</a>.
    </li>
    <li>
        <strong>Google Maps.</strong> Restaurant and hotel pages show an embedded Google map. Google receives your IP address
        and may set its own cookies when the map loads.
    </li>
    <li>
        <strong>Share buttons.</strong> The Facebook, X and WhatsApp share buttons are plain links.
        Nothing is sent to those services unless you click one.
    </li>
</ul>

<h2>Your rights</h2>
<p>
    Under the GDPR you can ask us to see, correct or delete your personal data, to limit how we use it,
    or object to its use.
    Send your request through our <a href="{{ route('contact') }}">contact form</a> and we will reply within one month.
    You also have the right to complain to the data protection authority in your country.
</p>

<h2>Changes</h2>
<p>If we change how we handle data, we will update this page and the date at the top.</p>
@endsection
