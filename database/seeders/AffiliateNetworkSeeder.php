<?php

namespace Database\Seeders;

use App\Models\AffiliateNetwork;
use Illuminate\Database\Seeder;

class AffiliateNetworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AffiliateNetwork::firstOrCreate(['slug' => 'agoda'], [
            'name' => 'Agoda',
            'category' => 'hotels',
            'website' => 'https://www.agoda.com',
            // Set in the admin under Listings → Affiliate Networks
            'affiliate_id' => env('AGODA_AFFILIATE_ID'),
            'is_active' => true,
        ]);
    }
}
