<?php

namespace App\Console\Commands;

use App\Models\Accommodation;
use App\Models\AffiliateNetwork;
use App\Models\Destination;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;

class ImportAgodaHotels extends Command
{
    protected $signature = 'agoda:import
        {file=storage/app/private/hotels/agoda/hotels.xlsx : Agoda hotel feed (.xlsx)}
        {--status=published : Status for newly imported hotels (draft or published)}
        {--limit= : Only import the first N rows}';

    protected $description = 'Import or update hotels, photos and affiliate links from an Agoda feed';

    /**
     * Affiliate link pattern; {affiliate_id} is filled in from the Agoda network
     * when the link is used, so changing the ID there updates every hotel.
     */
    public const LINK = 'https://www.agoda.com/partners/partnersearch.aspx?pcs=1&cid={affiliate_id}&hl=en-us&hid=';

    /** Feed city => destination slug. */
    public const CITY_DESTINATIONS = [
        'Jakarta' => 'jakarta',
        'Bandung' => 'bandung',
        'Yogyakarta' => 'yogyakarta',
        'Surakarta' => 'solo',
        'Bromo' => 'mount-bromo',
        'Surabaya' => 'surabaya',
    ];

    /** Feed property type (first listed) => accommodation type. */
    public const TYPES = [
        'hotel' => 'hotel',
        'inn' => 'hotel',
        'motel' => 'hotel',
        'capsule hotel' => 'hotel',
        'love hotel' => 'hotel',
        'lodge' => 'hotel',
        'resort' => 'resort',
        'villa' => 'villa',
        'resort villa' => 'villa',
        'apartment/flat' => 'apartment',
        'serviced apartment' => 'apartment',
        'entire house' => 'house',
        'entire bungalow' => 'house',
        'townhouse' => 'house',
        'country house' => 'house',
        'dome house' => 'house',
        'guesthouse/bed and breakfast' => 'guesthouse',
        'guest house' => 'guesthouse',
        'bed & breakfast' => 'guesthouse',
        'homestay' => 'guesthouse',
        'hostel' => 'hostel',
    ];

    /** Columns the feed owns; anything edited in the admin (status, slug, SEO...) is kept on re-import. */
    protected const FEED_COLUMNS = [
        'name', 'type', 'description', 'short_description', 'address',
        'latitude', 'longitude', 'star_rating', 'affiliate_link', 'updated_at',
    ];

    protected const CHUNK = 500;

    protected AffiliateNetwork $network;

    /** @var array<string, int> */
    protected array $destinations = [];

    /** @var array<string, true> */
    protected array $slugs = [];

    protected array $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'images' => 0, 'linked' => 0];

    public function handle(): int
    {
        $file = base_path($this->argument('file'));
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        if (! in_array($this->option('status'), ['draft', 'published'])) {
            $this->error('--status must be draft or published.');

            return self::FAILURE;
        }

        $this->network = AffiliateNetwork::firstOrCreate(
            ['slug' => 'agoda'],
            ['name' => 'Agoda', 'category' => 'hotels', 'website' => 'https://www.agoda.com'],
        );

        if (! $this->network->affiliate_id) {
            $this->warn('The Agoda network has no affiliate ID yet; links will work once it is set in the admin.');
        }

        $this->destinations = Destination::whereIn('slug', self::CITY_DESTINATIONS)->pluck('id', 'slug')->all();
        $this->slugs = Accommodation::pluck('slug')->flip()->map(fn () => true)->all();

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $bar = $this->output->createProgressBar($limit ?? 0);
        $bar->start();

        $chunk = [];
        foreach ($this->rows($file) as $index => $row) {
            if ($limit !== null && $index >= $limit) {
                break;
            }

            $chunk[] = $row;
            if (count($chunk) === self::CHUNK) {
                $this->importChunk(collect($chunk));
                $bar->advance(count($chunk));
                $chunk = [];
            }
        }

        if ($chunk) {
            $this->importChunk(collect($chunk));
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Created', 'Updated', 'Skipped', 'Photos', 'Linked to destinations'], [array_values($this->stats)]);

        return self::SUCCESS;
    }

    /**
     * Stream the sheet as associative rows keyed by the header.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    protected function rows(string $file): \Generator
    {
        $reader = new Reader;
        $reader->open($file);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $header = null;
                $index = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $values = array_map(
                        fn ($value) => $value === 'None' || $value === '' ? null : $value,
                        $row->toArray(),
                    );

                    if ($header === null) {
                        $header = $values;

                        continue;
                    }

                    yield $index++ => array_combine($header, array_pad($values, count($header), null));
                }

                break; // Only the first sheet
            }
        } finally {
            $reader->close();
        }
    }

    protected function importChunk(Collection $rows): void
    {
        $total = $rows->count();
        $rows = $rows->filter(fn ($row) => filled($row['hotel_id'] ?? null) && filled($row['hotel_name'] ?? null))
            ->keyBy(fn ($row) => (string) $row['hotel_id']);
        $this->stats['skipped'] += $total - $rows->count();

        DB::transaction(function () use ($rows) {
            $existing = Accommodation::where('affiliate_network_id', $this->network->id)
                ->whereIn('external_id', $rows->keys())
                ->pluck('slug', 'external_id');

            $now = now();
            $records = $rows->map(function ($row, $hotelId) use ($existing, $now) {
                $description = $this->cleanText($row['overview'] ?? null);

                return [
                    'affiliate_network_id' => $this->network->id,
                    'external_id' => $hotelId,
                    'name' => Str::limit(trim($row['hotel_name']), 250, ''),
                    'slug' => $existing[$hotelId] ?? $this->uniqueSlug($row['hotel_name'], $hotelId),
                    'type' => $this->type($row['accommodation_type'] ?? null),
                    'description' => $description,
                    'short_description' => $description ? Str::limit($description, 200) : null,
                    'address' => $row['addressline1'] ? Str::limit(trim($row['addressline1']), 250, '') : null,
                    'latitude' => is_numeric($row['latitude'] ?? null) ? (float) $row['latitude'] : null,
                    'longitude' => is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null,
                    'star_rating' => is_numeric($row['star_rating'] ?? null) && $row['star_rating'] >= 1 ? (int) floor($row['star_rating']) : null,
                    'affiliate_link' => self::LINK . $hotelId,
                    'status' => $this->option('status'),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });

            DB::table('accommodations')->upsert($records->values()->all(), ['affiliate_network_id', 'external_id'], self::FEED_COLUMNS);

            $this->stats['updated'] += $existing->count();
            $this->stats['created'] += $rows->count() - $existing->count();

            $ids = Accommodation::where('affiliate_network_id', $this->network->id)
                ->whereIn('external_id', $rows->keys())
                ->pluck('id', 'external_id');

            $this->importPhotos($rows, $ids, $now);
            $this->linkDestinations($rows, $ids);
        });
    }

    /**
     * Replace each hotel's feed photos. Images uploaded in the admin are kept.
     */
    protected function importPhotos(Collection $rows, Collection $ids, $now): void
    {
        $images = DB::table('images')
            ->where('imageable_type', Accommodation::class)
            ->whereIn('imageable_id', $ids->values());

        // Hotels whose featured image was chosen from an admin upload keep it featured
        $uploadedFeatured = (clone $images)->where('path', 'not like', 'http%')->where('is_featured', true)
            ->pluck('imageable_id')->flip();

        (clone $images)->where('path', 'like', 'http%')->delete();

        $photos = [];
        foreach ($rows as $hotelId => $row) {
            $order = 0;
            foreach (range(1, 5) as $n) {
                $url = $row["photo{$n}"] ?? null;
                if (! $url || ! str_contains($url, 'agoda.net')) {
                    continue;
                }

                $photos[] = [
                    'imageable_type' => Accommodation::class,
                    'imageable_id' => $ids[$hotelId],
                    'path' => $this->photoUrl($url),
                    'alt_text' => Str::limit($row['hotel_name'], 250, ''),
                    'order' => ++$order,
                    'is_featured' => $order === 1 && ! isset($uploadedFeatured[$ids[$hotelId]]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($photos, 1000) as $batch) {
            DB::table('images')->insert($batch);
        }

        $this->stats['images'] += count($photos);
    }

    protected function linkDestinations(Collection $rows, Collection $ids): void
    {
        $links = $rows->map(function ($row, $hotelId) use ($ids) {
            $slug = self::CITY_DESTINATIONS[trim((string) ($row['city'] ?? ''))] ?? null;
            $destinationId = $slug ? ($this->destinations[$slug] ?? null) : null;

            return $destinationId ? [
                'destination_id' => $destinationId,
                'accommodation_id' => $ids[$hotelId],
                'is_primary' => true,
            ] : null;
        })->filter()->values()->all();

        $this->stats['linked'] += DB::table('destination_accommodation')->insertOrIgnore($links);
    }

    protected function type(?string $feedType): string
    {
        $first = Str::lower(trim(Str::before((string) $feedType, ',')));

        return self::TYPES[$first] ?? 'other';
    }

    /**
     * Resize to fit 1024x768, which suits cards and page banners. Agoda's other
     * resize options (including the feed's own ?s=312x&ce=0) return a blank GIF
     * for over half of all photos; this one works for all of them.
     */
    protected function photoUrl(string $url): string
    {
        $path = Str::before(preg_replace('/^http:/', 'https:', $url), '?');

        return "{$path}?s=1024x768";
    }

    /**
     * The feed joins sentences without a space ("parking.Reception"); restore it.
     */
    protected function cleanText(?string $text): ?string
    {
        if (blank($text)) {
            return null;
        }

        return trim(preg_replace('/([a-z0-9)])([.!?])(?=[A-Z])/', '$1$2 ', $text));
    }

    protected function uniqueSlug(string $name, string $hotelId): string
    {
        $slug = Str::slug($name) ?: 'hotel';
        if (isset($this->slugs[$slug])) {
            $slug .= "-{$hotelId}";
        }

        $this->slugs[$slug] = true;

        return $slug;
    }
}
