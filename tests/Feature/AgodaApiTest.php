<?php

use App\Support\Agoda\AgodaClient;
use App\Support\Agoda\Offer;
use App\Support\Agoda\Stay;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const AGODA_URL = 'https://agoda.test/lt_v1';

beforeEach(function () {
    config(['services.agoda.url' => AGODA_URL, 'services.agoda.api_key' => '1234567:secret-key', 'services.agoda.site_id' => null, 'services.agoda.currency' => 'EUR']);
    $this->checkIn = CarbonImmutable::parse('+14 days')->startOfDay();
});

function agodaResult(int $hotelId, array $overrides = []): array
{
    return [
        'hotelId' => $hotelId, 'hotelName' => "Hotel {$hotelId}", 'starRating' => 3.0, 'reviewScore' => 8.6, 'reviewCount' => 1240,
        'currency' => 'EUR', 'dailyRate' => 23.4, 'crossedOutRate' => 0.0, 'discountPercentage' => 0.0,
        'includeBreakfast' => true, 'freeWifi' => true, ...$overrides,
    ];
}

test('it asks Agoda for the hotels with the key in the header and the body', function () {
    Http::fake([AGODA_URL => Http::response(['results' => [agodaResult(11), agodaResult(22, ['dailyRate' => 50])]])]);

    $offers = app(AgodaClient::class)->offers([22, 11, 11], $this->checkIn, $this->checkIn->addDays(2), 3, 1);

    expect($offers)->toHaveKeys([11, 22])
        ->and($offers[11])->toBeInstanceOf(Offer::class)
        ->and($offers[11]->dailyRate)->toBe(23.4)
        ->and($offers[11]->reviewScore)->toBe(8.6)
        ->and($offers[11]->breakfast)->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->url() === AGODA_URL
        && $request->header('Authorization') === ['1234567:secret-key']
        && $request['siteId'] === 1234567
        && $request['apiKey'] === 'secret-key'
        && $request['criteria']['hotelId'] === [11, 22]
        && $request['criteria']['checkInDate'] === $this->checkIn->toDateString()
        && $request['criteria']['checkOutDate'] === $this->checkIn->addDays(2)->toDateString()
        && $request['criteria']['additional']['occupancy'] === ['numberOfAdult' => 3, 'numberOfChildren' => 1]
        && $request['criteria']['additional']['currency'] === 'EUR');
});

test('a separate site id is used when the key has none', function () {
    config(['services.agoda.api_key' => 'plain-key', 'services.agoda.site_id' => '999']);
    Http::fake([AGODA_URL => Http::response(['results' => []])]);

    app(AgodaClient::class)->offers([1], $this->checkIn, $this->checkIn->addDay());

    Http::assertSent(fn (Request $request) => $request->header('Authorization') === ['999:plain-key'] && $request['siteId'] === 999);
});

test('large lists are split into requests of 100', function () {
    Http::fake([AGODA_URL => Http::response(['results' => []])]);

    app(AgodaClient::class)->offers(range(1, 250), $this->checkIn, $this->checkIn->addDay());

    Http::assertSentCount(3);
});

test('results are cached, failures are not', function () {
    Http::fakeSequence(AGODA_URL)
        ->push(['error' => ['id' => 500, 'message' => 'Oops']], 500)
        ->push(['results' => [agodaResult(1)]])
        ->push(['results' => [agodaResult(1, ['dailyRate' => 99])]]);
    $agoda = app(AgodaClient::class);

    expect($agoda->offers([1], $this->checkIn, $this->checkIn->addDay()))->toBe([])
        ->and($agoda->offers([1], $this->checkIn, $this->checkIn->addDay())[1]->dailyRate)->toBe(23.4)
        ->and($agoda->offers([1], $this->checkIn, $this->checkIn->addDay())[1]->dailyRate)->toBe(23.4);

    Http::assertSentCount(2);
});

test('the random "Site ID is invalid" answer is retried', function () {
    Http::fakeSequence(AGODA_URL)
        ->push(['error' => ['id' => 101, 'message' => 'Site ID 1234567 is invalid']], 401)
        ->push(['results' => [agodaResult(1)]]);

    expect(app(AgodaClient::class)->offers([1], $this->checkIn, $this->checkIn->addDay()))->toHaveKey(1);
});

test('no rooms, errors, timeouts and a missing key all give no offers', function (Closure $setup) {
    $setup();

    expect(app(AgodaClient::class)->offers([1], $this->checkIn, $this->checkIn->addDay()))->toBe([]);
})->with([
    'no rooms (911)' => [fn () => Http::fake([AGODA_URL => Http::response(['error' => ['id' => 911, 'message' => 'No search result']])])],
    'quota exceeded' => [fn () => Http::fake([AGODA_URL => Http::response('', 403)])],
    'server error' => [fn () => Http::fake([AGODA_URL => Http::response('<html>', 503)])],
    'timeout' => [fn () => Http::fake([AGODA_URL => fn () => throw new Illuminate\Http\Client\ConnectionException('timed out')])],
    'no key' => [function () {
        config(['services.agoda.api_key' => null]);
        Http::fake();
    }],
]);

test('a crossed-out price gives a discount even when Agoda says 0%', function () {
    $offer = Offer::fromApi(agodaResult(1, ['dailyRate' => 5.5, 'crossedOutRate' => 10.0, 'discountPercentage' => 0]));
    expect($offer->crossedOutRate)->toBe(10.0)->and($offer->discountPercent)->toBe(45);

    $plain = Offer::fromApi(agodaResult(1, ['dailyRate' => 20, 'crossedOutRate' => 0, 'reviewScore' => 0, 'reviewCount' => 0]));
    expect($plain->crossedOutRate)->toBeNull()->and($plain->discountPercent)->toBe(0)->and($plain->reviewScore)->toBeNull();
});

test('a stay is read from the URL only when it makes sense', function (array $query, ?string $label) {
    expect(Stay::fromQuery($query)?->label())->toBe($label);
})->with([
    'over a year ahead' => [['checkin' => '2030-01-08', 'checkout' => '2030-01-10'], null],
    'past' => [['checkin' => '2020-01-08', 'checkout' => '2020-01-10'], null],
    'wrong order' => [['checkin' => '2030-01-10', 'checkout' => '2030-01-08'], null],
    'not a date' => [['checkin' => '2030-02-30', 'checkout' => '2030-03-02'], null],
    'too many adults' => [['checkin' => '2030-01-08', 'checkout' => '2030-01-10', 'adults' => '40'], null],
]);

test('a stay knows its nights and label', function () {
    $stay = Stay::fromQuery(['checkin' => today()->addDays(3)->toDateString(), 'checkout' => today()->addDays(5)->toDateString(), 'adults' => 3, 'children' => 1]);

    expect($stay->nights())->toBe(2)
        ->and($stay->label())->toEndWith('· 2 nights · 3 adults, 1 child')
        ->and(Stay::fromQuery(['checkin' => today()->addYears(2)->toDateString(), 'checkout' => today()->addYears(2)->addDay()->toDateString()]))->toBeNull()
        ->and(Stay::fromQuery(['checkin' => today()->toDateString(), 'checkout' => today()->addDays(31)->toDateString()]))->toBeNull();
});
