<?php

namespace App\Http\Controllers;

use App\Models\AffiliateNetwork;

class LegalController extends Controller
{
    public function privacy()
    {
        return view('front.pages.legal.privacy');
    }

    public function affiliateDisclosure()
    {
        $networks = AffiliateNetwork::where('is_active', true)->orderBy('name')->pluck('name');

        return view('front.pages.legal.affiliate-disclosure', compact('networks'));
    }
}
