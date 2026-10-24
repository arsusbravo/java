<?php

namespace App\Console\Commands;

use App\Models\Accommodation;
use App\Support\Agoda\AgodaClient;
use App\Support\Agoda\Offer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Daily refresh of the "From EUR x / night" price and the Agoda guest rating
 * shown on hotel cards, for a default stay (one night, two adults, two weeks ahead).
 */
class AgodaPricesCommand extends Command
{
    protected $signature = 'agoda:prices {--limit= : Only refresh this many hotels (for testing)}';

    protected $description = 'Refresh hotel prices and guest ratings from the Agoda API';

    /** The default stay starts this many days from today. */
    public const DAYS_AHEAD = 14;

    /** Pause between requests, to stay well within Agoda's quota. */
    protected const PAUSE_MICROSECONDS = 500_000;

    public function handle(AgodaClient $agoda): int
    {
        if (! $agoda->isConfigured()) {
            $this->error('Add AGODA_API_KEY (and AGODA_SITE_ID) to .env first.');

            return self::FAILURE;
        }

        $checkIn = now()->toImmutable()->startOfDay()->addDays(self::DAYS_AHEAD);
        $query = Accommodation::query()->published()->where('is_active', true)->whereNotNull('external_id')->orderBy('id');
        $total = min($query->count(), (int) ($this->option('limit') ?: PHP_INT_MAX));

        $this->info("Checking {$total} hotels for {$checkIn->toDateString()} (1 night, 2 adults)...");
        $bar = $this->output->createProgressBar($total);
        $priced = 0;
        $done = 0;

        foreach ($query->lazyById(AgodaClient::CHUNK_SIZE)->take($total)->chunk(AgodaClient::CHUNK_SIZE) as $hotels) {
            $offers = $agoda->offers($hotels->pluck('external_id')->all(), $checkIn, $checkIn->addDay(), cache: false);

            foreach ($hotels as $hotel) {
                $offer = $offers[(int) $hotel->external_id] ?? null;
                $priced += $offer ? 1 : 0;
                $this->store($hotel, $offer);
            }

            $bar->advance($hotels->count());
            $done += $hotels->count();
            if ($done < $total) {
                usleep(self::PAUSE_MICROSECONDS);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done: {$priced} of {$total} hotels have rooms for that night.");

        return self::SUCCESS;
    }

    /**
     * Without an offer the price is cleared (it would be stale), the rating is kept.
     */
    protected function store(Accommodation $hotel, ?Offer $offer): void
    {
        $values = $offer ? [
            'price_from' => $offer->dailyRate,
            'currency' => $offer->currency,
            'crossed_out_price' => $offer->crossedOutRate,
            'discount_percent' => $offer->discountPercent ?: null,
            'review_score' => $offer->reviewScore ?? $hotel->review_score,
            'review_count' => $offer->reviewCount ?? $hotel->review_count,
            'includes_breakfast' => $offer->breakfast,
            'free_wifi' => $offer->wifi,
        ] : [
            'price_from' => null,
            'crossed_out_price' => null,
            'discount_percent' => null,
        ];

        // Plain query, so updated_at keeps meaning "edited in the admin"
        DB::table('accommodations')->where('id', $hotel->id)->update([...$values, 'prices_checked_at' => now()]);
    }
}
