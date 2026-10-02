<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            [
                'name' => 'West Java',
                'slug' => 'west-java',
                'description' => 'West Java (Jawa Barat) is known for its cool highlands, tea plantations, volcanic landscapes, and vibrant cities. Home to Bandung, the cultural capital, and Jakarta, Indonesia\'s bustling metropolis. Experience the beauty of Tangkuban Perahu volcano, the tranquility of Kawah Putih crater lake, and the charm of colonial architecture.',
                'image' => null,
                'meta_title' => 'Explore West Java - Destinations, Tours & Travel Guide',
                'meta_description' => 'Discover the best of West Java including Bandung, Jakarta, Bogor, and more. Find top accommodations, tours, and culinary experiences in West Java, Indonesia.',
            ],
            [
                'name' => 'Central Java',
                'slug' => 'central-java',
                'description' => 'Central Java (Jawa Tengah) is the cultural heart of Java, home to ancient temples, royal palaces, and traditional Javanese arts. Visit the magnificent Borobudur and Prambanan temples, explore the royal city of Yogyakarta, and witness the active Mount Merapi volcano. Rich in history, culture, and natural beauty.',
                'image' => null,
                'meta_title' => 'Central Java Travel Guide - Yogyakarta, Solo & Borobudur',
                'meta_description' => 'Experience the rich culture and heritage of Central Java. Explore Yogyakarta, Solo, Semarang, Borobudur Temple, and traditional Javanese arts and cuisine.',
            ],
            [
                'name' => 'East Java',
                'slug' => 'east-java',
                'description' => 'East Java (Jawa Timur) offers dramatic volcanic landscapes, stunning beaches, and vibrant city life. Famous for Mount Bromo\'s sunrise views, the otherworldly blue flames of Ijen Crater, and the bustling port city of Surabaya. Adventure seekers and nature lovers will find paradise in East Java\'s diverse terrain.',
                'image' => null,
                'meta_title' => 'East Java Adventures - Mount Bromo, Ijen & Surabaya',
                'meta_description' => 'Explore East Java\'s natural wonders including Mount Bromo, Ijen Crater, and Malang. Discover the best tours, accommodations, and travel tips for East Java, Indonesia.',
            ],
        ];

        foreach ($regions as $region) {
            Region::create($region);
        }
    }
}