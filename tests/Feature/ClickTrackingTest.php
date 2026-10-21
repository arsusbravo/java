<?php

use App\Models\Accommodation;
use App\Models\AffiliateClick;
use App\Models\AffiliateNetwork;
use App\Models\Destination;
use Database\Seeders\DatabaseSeeder;

const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $agoda = AffiliateNetwork::where('slug', 'agoda')->firstOrFail();
    $agoda->update(['affiliate_id' => '1976274']);

    $this->hotel = Accommodation::create([
        'name' => 'Hotel Tugu', 'slug' => 'hotel-tugu', 'type' => 'hotel',
        'status' => 'published', 'is_active' => true, 'affiliate_network_id' => $agoda->id,
        'affiliate_link' => 'https://www.agoda.com/partners/partnersearch.aspx?pcs=1&cid={affiliate_id}&hl=en-us&hid=255678',
    ]);
});

function book(array $headers = [], string $from = 'destination', string $ip = '203.0.113.57')
{
    return test()->withHeaders(['User-Agent' => BROWSER, ...$headers])
        ->withServerVariables(['REMOTE_ADDR' => $ip])
        ->get('/go/hotel/' . test()->hotel->id . '?from=' . $from);
}

test('a booking click is recorded and the visitor goes on to the partner', function () {
    $updatedAt = $this->hotel->updated_at;
    $this->travel(5)->minutes();

    book(['Referer' => url('/destinations/central-java/yogyakarta') . '?utm=x'])
        ->assertRedirect('https://www.agoda.com/partners/partnersearch.aspx?pcs=1&cid=1976274&hl=en-us&hid=255678');

    $click = AffiliateClick::sole();
    expect($click->clickable->is($this->hotel))->toBeTrue()
        ->and($click->source)->toBe('destination')
        ->and($click->ip_address)->toBe('203.0.113.0')                     // anonymised
        ->and($click->referrer)->toBe('/destinations/central-java/yogyakarta') // no query string
        ->and($click->user_agent)->toContain('Chrome')
        ->and($this->hotel->fresh()->clicks_count)->toBe(1)
        ->and($this->hotel->fresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});

test('ipv6 addresses are shortened too', function () {
    book(ip: '2001:db8:85a3:8d3:1319:8a2e:370:7348');

    expect(AffiliateClick::sole()->ip_address)->toBe('2001:db8:85a3::');
});

test('unknown sources are stored as other', function () {
    book(from: 'newsletter<script>');

    expect(AffiliateClick::sole()->source)->toBe('other');
});

test('bots and repeat clicks are not counted, but still redirected', function () {
    book(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])->assertRedirect();
    book(['User-Agent' => 'facebookexternalhit/1.1'])->assertRedirect();
    expect(AffiliateClick::count())->toBe(0);

    book()->assertRedirect();
    book()->assertRedirect();          // same visitor again within 10 minutes
    expect(AffiliateClick::count())->toBe(1);

    $this->travel(11)->minutes();
    book();
    expect(AffiliateClick::count())->toBe(2)
        ->and($this->hotel->fresh()->clicks_count)->toBe(2);
});

test('only live listings with a booking link can be clicked', function (array $attributes) {
    $this->hotel->update($attributes);

    book()->assertNotFound();
    expect(AffiliateClick::count())->toBe(0);
})->with([
    'draft' => [['status' => 'draft']],
    'inactive' => [['is_active' => false]],
    'no link' => [['affiliate_link' => null]],
]);

test('unknown listing types and ids are not found', function () {
    $this->get('/go/castle/1')->assertNotFound();
    $this->get('/go/hotel/999999')->assertNotFound();
});

test('destination pages link to bookings through the tracker', function () {
    $destination = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
    $destination->accommodations()->attach($this->hotel);

    $this->get("/destinations/{$destination->region->slug}/yogyakarta")
        ->assertOk()
        ->assertSee('/go/hotel/' . $this->hotel->id . '?from=destination', false)
        ->assertSee('rel="nofollow sponsored noopener"', false)
        ->assertDontSee('cid=1976274', false);   // the Agoda link itself stays behind the redirect
});

test('the dashboard shows the most clicked listings and where clicks come from', function () {
    book();
    $this->actingAs(App\Models\User::firstOrFail())
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('topClicked.0.name', 'Hotel Tugu')
            ->where('topClicked.0.clicks', 1)
            ->where('topClicked.0.editUrl', '/admin/accommodations/' . $this->hotel->id . '/edit')
            ->where('clicksBySource.0', ['source' => 'Destination page', 'clicks' => 1]));
});
