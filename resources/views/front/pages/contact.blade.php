@extends('front.pages.legal.layout')

@section('title', 'Contact Us | Java Sunrise')
@section('meta_description', 'Questions about travelling in Java, a correction or a suggestion for JavaSunrise.com? Send us a message.')
@section('heading', 'Contact Us')
@section('subheading', 'Questions about Java, a correction, or a tip for other travellers? We read every message.')

@section('body')
@if(session('sent'))
    <div class="rounded-2xl border border-green-200 bg-green-50 p-6 text-green-800" role="status">
        <p class="font-semibold">Thank you, your message has been sent.</p>
        <p class="mt-1 text-sm">We'll reply to the email address you gave us.</p>
    </div>
@endif

<form method="POST" action="{{ route('contact.store') }}" class="space-y-5" novalidate>
    @csrf

    {{-- Honeypot: hidden from people, filled in by spam bots --}}
    <div class="hidden" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="name" class="block text-sm font-semibold text-gray-900">Your name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name"
                   class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-java-accent focus:ring-2 focus:ring-java-accent/30 focus:outline-none">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-semibold text-gray-900">Your email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="191" autocomplete="email"
                   class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-java-accent focus:ring-2 focus:ring-java-accent/30 focus:outline-none">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="subject" class="block text-sm font-semibold text-gray-900">Subject <span class="font-normal text-gray-500">(optional)</span></label>
        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" maxlength="150"
               class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-java-accent focus:ring-2 focus:ring-java-accent/30 focus:outline-none">
        @error('subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="message" class="block text-sm font-semibold text-gray-900">Message</label>
        <textarea id="message" name="message" rows="6" required maxlength="5000"
                  class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-java-accent focus:ring-2 focus:ring-java-accent/30 focus:outline-none">{{ old('message') }}</textarea>
        @error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('website')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <p class="text-sm text-gray-500">
        We use your name and email only to reply to you. See our <a href="{{ route('privacy') }}">privacy policy</a>.
    </p>

    <button type="submit" class="rounded-full bg-java-accent px-8 py-3 font-semibold text-white hover:bg-orange-600 transition-colors">
        Send message
    </button>
</form>
@endsection
