<?php

use App\Models\Accommodation;
use App\Models\AffiliateNetwork;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::firstOrFail());
});

test('agoda is seeded as a hotel network', function () {
    $agoda = AffiliateNetwork::where('slug', 'agoda')->firstOrFail();

    expect($agoda->name)->toBe('Agoda')
        ->and($agoda->category)->toBe('hotels')
        ->and($agoda->is_active)->toBeTrue();
});

test('networks can be managed in the admin', function () {
    $this->get('/admin/affiliate-networks')->assertOk()->assertSee('Agoda');

    $this->post('/admin/affiliate-networks', [
        'name' => 'GetYourGuide',
        'affiliate_id' => 'GYG-123',
        'category' => 'tours',
        'website' => 'https://www.getyourguide.com',
        'default_commission_rate' => '8',
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $network = AffiliateNetwork::where('slug', 'getyourguide')->firstOrFail();
    expect($network->affiliate_id)->toBe('GYG-123')
        ->and($network->default_commission_rate)->toBe('8.00');

    $this->post('/admin/affiliate-networks', ['name' => 'Agoda', 'category' => 'space'])
        ->assertSessionHasErrors(['slug', 'category']);
});

test('listings link to a network and fill the affiliate id into their link', function () {
    $agoda = AffiliateNetwork::where('slug', 'agoda')->firstOrFail();
    $agoda->update(['affiliate_id' => '1234567', 'default_commission_rate' => 5]);

    $this->post('/admin/accommodations', [
        'name' => 'Hotel Tugu',
        'type' => 'hotel',
        'status' => 'published',
        'currency' => 'IDR',
        'affiliate_link' => 'https://www.agoda.com/partners/partnersearch.aspx?cid={affiliate_id}&hid=987',
        'affiliate_network_id' => $agoda->id,
    ])->assertSessionHasNoErrors();

    $hotel = Accommodation::where('slug', 'hotel-tugu')->firstOrFail();
    expect($hotel->affiliateNetwork->is($agoda))->toBeTrue()
        ->and($hotel->affiliate_url)->toBe('https://www.agoda.com/partners/partnersearch.aspx?cid=1234567&hid=987')
        ->and($hotel->commission_rate)->toBe('5.00');

    // Changing the ID on the network updates every link
    $agoda->update(['affiliate_id' => '7654321']);
    expect($hotel->fresh()->affiliate_url)->toContain('cid=7654321');

    $this->get("/admin/accommodations?affiliate_network_id={$agoda->id}")
        ->assertOk()
        ->assertSee('Hotel Tugu');
});

test('restaurants have no commission and keep working with a network', function () {
    $agoda = AffiliateNetwork::firstOrFail();
    $agoda->update(['default_commission_rate' => 5]);

    $this->post('/admin/restaurants', [
        'name' => 'Warung Tugu',
        'status' => 'draft',
        'affiliate_network_id' => $agoda->id,
    ])->assertSessionHasNoErrors();

    expect(Restaurant::where('slug', 'warung-tugu')->firstOrFail()->affiliate_network_id)->toBe($agoda->id);
});

test('deleting a network unlinks its listings instead of deleting them', function () {
    $agoda = AffiliateNetwork::firstOrFail();
    $hotel = Accommodation::create(['name' => 'Hotel X', 'slug' => 'hotel-x', 'type' => 'hotel', 'affiliate_network_id' => $agoda->id]);

    $this->delete("/admin/affiliate-networks/{$agoda->id}")->assertRedirect('/admin/affiliate-networks');

    expect($hotel->fresh())->not->toBeNull()
        ->and($hotel->fresh()->affiliate_network_id)->toBeNull();
});
