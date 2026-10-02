<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Region;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $westJava = Region::where('slug', 'west-java')->first();
        $centralJava = Region::where('slug', 'central-java')->first();
        $eastJava = Region::where('slug', 'east-java')->first();

        $destinations = [
            // West Java
            [
                'region_id' => $westJava->id,
                'name' => 'Jakarta',
                'slug' => 'jakarta',
                'description' => 'Jakarta, Indonesia\'s vibrant capital city, is a melting pot of cultures, cuisines, and commerce. This bustling metropolis offers everything from historic landmarks like the National Monument (Monas) and Kota Tua (Old Town) to modern shopping malls, diverse culinary scenes, and exciting nightlife. Experience the energy of Southeast Asia\'s largest city.',
                'featured_image' => null,
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'meta_title' => 'Jakarta Travel Guide - Things to Do, Hotels & Tours',
                'meta_description' => 'Discover Jakarta, Indonesia\'s dynamic capital. Find the best hotels, tours, restaurants, and attractions in Jakarta.',
                'is_featured' => false,
                'order' => 1,
            ],
            [
                'region_id' => $westJava->id,
                'name' => 'Bandung',
                'slug' => 'bandung',
                'description' => 'Bandung, known as the "Paris of Java," is a cool highland city famous for its Art Deco architecture, creative culture, factory outlets, and stunning natural surroundings. Explore volcanic craters like Tangkuban Perahu and Kawah Putih, enjoy fresh strawberries from local farms, and indulge in Bandung\'s famous culinary scene. Perfect for a weekend getaway.',
                'featured_image' => null,
                'latitude' => -6.9175,
                'longitude' => 107.6191,
                'meta_title' => 'Bandung Travel Guide - Volcanoes, Shopping & Cool Climate',
                'meta_description' => 'Explore Bandung, West Java\'s cultural capital. Discover volcanic attractions, factory outlets, tea plantations, and the best hotels and tours.',
                'is_featured' => false,
                'order' => 2,
            ],

            // Central Java
            [
                'region_id' => $centralJava->id,
                'name' => 'Yogyakarta',
                'slug' => 'yogyakarta',
                'description' => 'Yogyakarta (Jogja) is the cultural soul of Java, home to ancient temples, traditional arts, and the Sultan\'s Palace. Immerse yourself in authentic Javanese culture through batik workshops, traditional wayang puppet shows, and exquisite local cuisine. Visit the magnificent Borobudur and Prambanan temples, explore art galleries, and experience the warmth of Javanese hospitality. A paradise for culture enthusiasts and food lovers.',
                'featured_image' => 'images/javanese_dinner.jpg',
                'latitude' => -7.7956,
                'longitude' => 110.3695,
                'meta_title' => 'Yogyakarta Culture - Traditional Arts, Cuisine & Temples',
                'meta_description' => 'Experience authentic Yogyakarta culture. Discover traditional Javanese arts, local cuisine, Borobudur Temple, and cultural heritage.',
                'is_featured' => true,
                'order' => 1,
            ],
            [
                'region_id' => $centralJava->id,
                'name' => 'Solo',
                'slug' => 'solo',
                'description' => 'Solo (Surakarta) is a charming royal city that offers a more laid-back alternative to Yogyakarta. Known for its authentic Javanese culture, traditional markets, batik production, and delicious street food. Visit the Keraton (royal palace), explore Pasar Klewer market, and enjoy the city\'s slower pace while experiencing genuine Central Javanese traditions.',
                'featured_image' => null,
                'latitude' => -7.5755,
                'longitude' => 110.8243,
                'meta_title' => 'Solo (Surakarta) Travel Guide - Royal Culture & Batik',
                'meta_description' => 'Discover Solo, Central Java\'s royal city. Experience traditional Javanese culture, batik markets, local cuisine, and authentic cultural attractions.',
                'is_featured' => false,
                'order' => 2,
            ],

            // East Java
            [
                'region_id' => $eastJava->id,
                'name' => 'Mount Bromo',
                'slug' => 'mount-bromo',
                'description' => 'Mount Bromo is one of Indonesia\'s most iconic volcanic landscapes and offers one of nature\'s most spectacular displays. Witness breathtaking sunrises over the volcanic landscape of the Bromo-Tengger-Semeru National Park, where the golden light illuminates the Sea of Sand and reveals the smoking crater in all its glory. Trek across the lunar-like landscape, ride horses to the crater rim, and experience the otherworldly beauty of this active volcano. A must-visit destination for nature lovers and photographers.',
                'featured_image' => 'images/sunrise.png',
                'latitude' => -7.9425,
                'longitude' => 112.9531,
                'meta_title' => 'Mount Bromo Sunrise - Iconic Volcanic Landscape East Java',
                'meta_description' => 'Experience the legendary Mount Bromo sunrise. Discover tours, accommodations, and travel tips for visiting East Java\'s most iconic volcano.',
                'is_featured' => true,
                'order' => 1,
            ],
            [
                'region_id' => $eastJava->id,
                'name' => 'Surabaya',
                'slug' => 'surabaya',
                'description' => 'Surabaya, Indonesia\'s second-largest city, is a vibrant port city known for its bustling markets and local treasures. Shop for authentic Indonesian souvenirs, traditional crafts, and local delicacies at famous markets like Pasar Atom and Tunjungan Plaza. Known as the "City of Heroes," Surabaya blends colonial heritage with modern development, offering historic sites, vibrant street food scenes, excellent seafood, and serves as the gateway to Mount Bromo and other East Java attractions.',
                'featured_image' => 'images/surabayasouvenir.jpg',
                'latitude' => -7.2575,
                'longitude' => 112.7521,
                'meta_title' => 'Surabaya Markets - Shop Local Treasures & Indonesian Souvenirs',
                'meta_description' => 'Explore Surabaya\'s vibrant markets and local treasures. Find hotels, restaurants, and shopping destinations in East Java\'s largest city.',
                'is_featured' => true,
                'order' => 2,
            ],
        ];

        foreach ($destinations as $destination) {
            Destination::create($destination);
        }
    }
}