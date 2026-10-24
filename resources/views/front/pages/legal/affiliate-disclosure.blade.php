@extends('front.pages.legal.layout')

@section('title', 'Affiliate Disclosure | Java Sunrise')
@section('meta_description', 'How JavaSunrise.com earns money: commission on bookings made through our partner links, at no extra cost to you.')
@section('heading', 'Affiliate Disclosure')
@section('subheading', 'How this site earns money, in plain words.')

@section('body')
<p>
    JavaSunrise.com is free to use. To pay for it, we take part in the affiliate programmes of
    {{ $networks->isNotEmpty() ? $networks->join(', ', ' and ') : 'booking partners' }}.
</p>

<h2>What that means for you</h2>
<ul>
    <li>Some links on this site are <strong>affiliate links</strong>: the "Book Now", "Book Tour" and "Check prices" buttons for hotels, tours and restaurants.</li>
    <li>If you click one and then make a booking on the partner's site, we receive a <strong>commission</strong> from that partner.</li>
    <li>You <strong>don't pay more</strong>. The price is the same as when you go to the partner's site directly.</li>
    <li>Your booking is made with the partner, not with us. Prices, availability, payment, changes and cancellations are handled by the partner under their terms.</li>
</ul>

<h2>How we choose what to show</h2>
<p>
    Hotels shown on our pages come from our partners' listings and are selected automatically by location
    (the closest ones to each place) and, in the trip planner, by the budget you pick.
    Star ratings, photos, prices and guest ratings come from the partner. Prices are checked daily (or live when
    you enter dates), are per night and exclude taxes and fees, and can change: the final price is the one shown
    on the partner's site.
    A hotel appearing on our site is not a personal recommendation, and we have not stayed at every one of them.
</p>
<p>
    Our travel guides and destination pages are written independently. Partners do not pay to be mentioned
    and do not review our content before it is published.
</p>

<h2>Questions</h2>
<p>
    Send us a message through our <a href="{{ route('contact') }}">contact form</a>.
    How we handle data when you click a booking link is explained in our <a href="{{ route('privacy') }}">privacy policy</a>.
</p>
@endsection
