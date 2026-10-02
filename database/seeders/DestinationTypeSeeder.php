<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\DestinationType;
use Illuminate\Database\Seeder;

class DestinationTypeSeeder extends Seeder
{
    /** Starter types, in display order. */
    public const TYPES = [
        'shopping' => ['Shopping', 'Malls, traditional markets, outlets and souvenir hunting.'],
        'heritage-history' => ['Heritage & History', 'Temples, palaces, old towns and historic landmarks.'],
        'culture-arts' => ['Culture & Arts', 'Batik, wayang, gamelan, dance and local traditions.'],
        'nature-adventure' => ['Nature & Adventure', 'Waterfalls, forests, hiking and outdoor adventures.'],
        'beach-islands' => ['Beach & Islands', 'Beaches, islands, snorkelling and coastal escapes.'],
        'culinary' => ['Culinary', 'Street food, local specialities and food tours.'],
        'city-life' => ['City Life', 'Cafés, nightlife, modern attractions and urban energy.'],
        'mountains-volcanoes' => ['Mountains & Volcanoes', 'Volcano sunrises, crater hikes and cool highlands.'],
    ];

    /** First suggestions for the seeded destinations; only used while they have no types. */
    public const SUGGESTIONS = [
        'jakarta' => ['city-life', 'shopping', 'culinary', 'heritage-history'],
        'bandung' => ['shopping', 'culinary', 'nature-adventure', 'mountains-volcanoes'],
        'yogyakarta' => ['heritage-history', 'culture-arts', 'culinary'],
        'solo' => ['heritage-history', 'culture-arts', 'shopping'],
        'mount-bromo' => ['mountains-volcanoes', 'nature-adventure'],
        'surabaya' => ['city-life', 'shopping', 'culinary'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $order = 0;
        foreach (self::TYPES as $slug => [$name, $description]) {
            DestinationType::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description, 'sort_order' => ++$order],
            );
        }

        $types = DestinationType::pluck('id', 'slug');
        Destination::whereIn('slug', array_keys(self::SUGGESTIONS))
            ->whereDoesntHave('types')
            ->each(fn (Destination $destination) => $destination->types()->attach(
                $types->only(self::SUGGESTIONS[$destination->slug])->values()
            ));
    }
}
