<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('front.pages.contact');
    }

    /**
     * Messages are kept in the database and read in the admin (/admin/contact-messages).
     */
    public function store(ContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::create($request->safe()->only(['name', 'email', 'subject', 'message']));

        return redirect()->route('contact')->with('sent', true);
    }
}
