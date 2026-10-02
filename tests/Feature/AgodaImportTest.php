<?php

use App\Models\Accommodation;
use App\Models\AffiliateNetwork;
use App\Models\Destination;
use App\Models\Image;
use Database\Seeders\DatabaseSeeder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Write a small feed with the same columns as Agoda's export.
 */
function agodaFeed(array $hotels): string
{
    $path = storage_path('framework/testing/agoda-' . uniqid() . '.xlsx');
    @mkdir(dirname($path), 0755, true);

    $columns = ['hotel_id', 'hotel_name', 'addressline1', 'city', 'state', 'star_rating', 'longitude', 'latitude',
        'photo1', 'photo2', 'photo3', 'photo4', 'photo5', 'overview', 'accommodation_type'];

    $writer = new Writer;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues($columns));
    foreach ($hotels as $hotel) {
        $writer->addRow(Row::fromValues(array_map(fn ($column) => $hotel[$column] ?? 'None', $columns)));
    }
    $writer->close();

    return $path;
}

function agodaHotel(array $overrides = []): array
{
    return [
        'hotel_id' => 255678,
        'hotel_name' => 'Hotel Tugu Malang',
        'addressline1' => 'Jl. Tugu 3, Malang',
        'city' => 'Malang',
        'state' => 'East Java',
        'star_rating' => 4.5,
        'longitude' => 112.634,
        'latitude' => -7.977,
        'photo1' => 'http://pix4.agoda.net/hotelimages/255678/-1/a.jpg?s=312x&ce=0',
        'photo2' => 'http://pix3.agoda.net/hotelimages/255678/-1/b.jpg?s=312x&ce=0',
        'overview' => 'Colonial charm.Free parking is available.',
        'accommodation_type' => 'Resort, Hotel',
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->agoda = AffiliateNetwork::where('slug', 'agoda')->firstOrFail();
    $this->agoda->update(['affiliate_id' => '1976274']);
});

afterEach(function () {
    foreach (glob(storage_path('framework/testing/agoda-*.xlsx')) as $file) {
        unlink($file);
    }
});

test('hotels are imported with mapped fields, photos and affiliate links', function () {
    $file = agodaFeed([
        agodaHotel(),
        agodaHotel(['hotel_id' => 999, 'hotel_name' => 'Kost Bromo', 'city' => 'Bromo', 'star_rating' => 0, 'accommodation_type' => 'Tent', 'photo1' => 'None', 'photo2' => 'None']),
        agodaHotel(['hotel_id' => 'None']),
    ]);

    $this->artisan('agoda:import', ['file' => str_replace(base_path() . '/', '', $file)])
        ->assertSuccessful();

    $hotel = Accommodation::where('external_id', '255678')->firstOrFail();
    expect($hotel->name)->toBe('Hotel Tugu Malang')
        ->and($hotel->slug)->toBe('hotel-tugu-malang')
        ->and($hotel->type)->toBe('resort')
        ->and($hotel->star_rating)->toBe(4)
        ->and($hotel->status)->toBe('published')
        ->and($hotel->description)->toBe('Colonial charm. Free parking is available.')
        ->and($hotel->affiliateNetwork->is($this->agoda))->toBeTrue()
        ->and($hotel->affiliate_url)->toBe('https://www.agoda.com/partners/partnersearch.aspx?pcs=1&cid=1976274&hl=en-us&hid=255678');

    expect($hotel->images()->orderBy('order')->pluck('path')->all())->toBe([
        'https://pix4.agoda.net/hotelimages/255678/-1/a.jpg?s=1024x768',
        'https://pix3.agoda.net/hotelimages/255678/-1/b.jpg?s=1024x768',
    ]);
    expect($hotel->featured_image_url)->toBe('https://pix4.agoda.net/hotelimages/255678/-1/a.jpg?s=1024x768');

    // Unrated, no photos, unknown type; linked to the Bromo destination
    $kost = Accommodation::where('external_id', '999')->firstOrFail();
    expect($kost->star_rating)->toBeNull()
        ->and($kost->type)->toBe('other')
        ->and($kost->images)->toHaveCount(0)
        ->and($kost->destinations->pluck('slug')->all())->toBe(['mount-bromo'])
        ->and($kost->featured_image_url)->toBe(asset('images/placeholders/destination.svg'));

    expect(Accommodation::count())->toBe(2);
});

test('changing the affiliate id updates every link', function () {
    $this->artisan('agoda:import', ['file' => str_replace(base_path() . '/', '', agodaFeed([agodaHotel()]))])->assertSuccessful();

    $this->agoda->update(['affiliate_id' => '5550001']);

    expect(Accommodation::firstOrFail()->affiliate_url)
        ->toBe('https://www.agoda.com/partners/partnersearch.aspx?pcs=1&cid=5550001&hl=en-us&hid=255678');
});

test('re-importing updates feed data but keeps admin edits and uploads', function () {
    $file = fn ($hotel) => str_replace(base_path() . '/', '', agodaFeed([$hotel]));
    $this->artisan('agoda:import', ['file' => $file(agodaHotel())])->assertSuccessful();

    $hotel = Accommodation::firstOrFail();
    $hotel->update(['status' => 'draft', 'is_featured' => true, 'meta_title' => 'Custom']);
    $upload = $hotel->images()->create(['path' => 'uploads/accommodations/own.jpg', 'is_featured' => true]);
    $hotel->images()->where('path', 'like', 'http%')->update(['is_featured' => false]);

    $this->artisan('agoda:import', ['file' => $file(agodaHotel(['hotel_name' => 'Hotel Tugu Malang Renamed', 'photo2' => 'None']))])
        ->assertSuccessful();

    $hotel->refresh();
    expect(Accommodation::count())->toBe(1)
        ->and($hotel->name)->toBe('Hotel Tugu Malang Renamed')
        ->and($hotel->slug)->toBe('hotel-tugu-malang')
        ->and($hotel->status)->toBe('draft')
        ->and($hotel->is_featured)->toBeTrue()
        ->and($hotel->meta_title)->toBe('Custom');

    // Feed photos replaced (now one), the admin upload kept and still the featured one
    expect($hotel->images()->where('path', 'like', 'http%')->count())->toBe(1)
        ->and($upload->fresh()->is_featured)->toBeTrue()
        ->and(Image::where('imageable_id', $hotel->id)->where('is_featured', true)->count())->toBe(1);
});

test('hotels in destination cities appear on the destination page with a booking link', function () {
    $this->artisan('agoda:import', ['file' => str_replace(base_path() . '/', '', agodaFeed([agodaHotel(['city' => 'Surakarta'])]))])
        ->assertSuccessful();

    $solo = Destination::where('slug', 'solo')->with('region')->firstOrFail();

    $this->get("/destinations/{$solo->region->slug}/solo")
        ->assertOk()
        ->assertSee('Hotel Tugu Malang')
        ->assertSee('cid=1976274&amp;hl=en-us&amp;hid=255678', false)
        ->assertSee('Resort')
        ->assertDontSee('/night');
});

test('duplicate names get a unique slug', function () {
    $file = str_replace(base_path() . '/', '', agodaFeed([
        agodaHotel(['hotel_id' => 1, 'hotel_name' => 'OYO 101']),
        agodaHotel(['hotel_id' => 2, 'hotel_name' => 'OYO 101']),
    ]));

    $this->artisan('agoda:import', ['file' => $file])->assertSuccessful();

    expect(Accommodation::orderBy('external_id')->pluck('slug')->all())->toBe(['oyo-101', 'oyo-101-2']);
});
