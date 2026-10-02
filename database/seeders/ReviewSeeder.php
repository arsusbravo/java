<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Destination;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some destinations to attach reviews to
        $bromo = Destination::where('slug', 'mount-bromo')->first();
        $yogyakarta = Destination::where('slug', 'yogyakarta')->first();
        $surabaya = Destination::where('slug', 'surabaya')->first();
        $jakarta = Destination::where('slug', 'jakarta')->first();
        $bandung = Destination::where('slug', 'bandung')->first();

        $reviews = [
            // Homepage Featured Reviews
            [
                'reviewable_id' => $bromo?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Sarah Martinez',
                'user_email' => 'sarah.martinez@example.com',
                'rating' => 5,
                'comment' => 'JavaSunrise made our trip unforgettable! The guides were spot-on and helped us discover places we never would have found on our own.',
                'status' => 'approved',
            ],
            [
                'reviewable_id' => $bromo?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'James Kim',
                'user_email' => 'james.kim@example.com',
                'rating' => 5,
                'comment' => 'The Mount Bromo sunrise experience was absolutely magical. Thanks to their detailed guide, we knew exactly when and where to go!',
                'status' => 'approved',
            ],
            [
                'reviewable_id' => $yogyakarta?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Aisha Patel',
                'user_email' => 'aisha.patel@example.com',
                'rating' => 5,
                'comment' => 'Best travel resource for Java! Authentic recommendations and beautifully written articles that capture the true essence of the island.',
                'status' => 'approved',
            ],

            // Additional Reviews for Mount Bromo
            [
                'reviewable_id' => $bromo?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Michael Chen',
                'user_email' => 'michael.chen@example.com',
                'rating' => 5,
                'comment' => 'Absolutely breathtaking! The sunrise tour was the highlight of our Indonesia trip. The lunar-like landscape is unlike anything I\'ve ever seen.',
                'status' => 'approved',
            ],
            [
                'reviewable_id' => $bromo?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Emma Wilson',
                'user_email' => 'emma.wilson@example.com',
                'rating' => 4,
                'comment' => 'Amazing experience! Just be prepared for the cold early morning temperatures. Bring warm clothes and you\'ll have an incredible time.',
                'status' => 'approved',
            ],

            // Reviews for Yogyakarta
            [
                'reviewable_id' => $yogyakarta?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Lucas Rodriguez',
                'user_email' => 'lucas.rodriguez@example.com',
                'rating' => 5,
                'comment' => 'Yogyakarta is a cultural treasure! From Borobudur to the local street food, everything exceeded our expectations. The people are incredibly warm and welcoming.',
                'status' => 'approved',
            ],
            [
                'reviewable_id' => $yogyakarta?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Sophie Dubois',
                'user_email' => 'sophie.dubois@example.com',
                'rating' => 5,
                'comment' => 'The batik workshops and traditional performances were fascinating. I could spend weeks exploring this city and still not see everything!',
                'status' => 'approved',
            ],

            // Reviews for Surabaya
            [
                'reviewable_id' => $surabaya?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'David Thompson',
                'user_email' => 'david.thompson@example.com',
                'rating' => 4,
                'comment' => 'Great city with authentic Indonesian atmosphere. The markets are incredible and the seafood is fresh and delicious. Perfect base for exploring East Java.',
                'status' => 'approved',
            ],
            [
                'reviewable_id' => $surabaya?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Maria Santos',
                'user_email' => 'maria.santos@example.com',
                'rating' => 5,
                'comment' => 'Loved shopping at the traditional markets! Found amazing souvenirs and the vendors were friendly and helpful. The city has so much character.',
                'status' => 'approved',
            ],

            // Reviews for Jakarta
            [
                'reviewable_id' => $jakarta?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Robert Anderson',
                'user_email' => 'robert.anderson@example.com',
                'rating' => 4,
                'comment' => 'Jakarta is a vibrant, bustling metropolis with amazing food scene. The street food is incredible and there\'s so much to see and do. Traffic can be challenging though!',
                'status' => 'approved',
            ],
            [
                'reviewable_id' => $jakarta?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Yuki Tanaka',
                'user_email' => 'yuki.tanaka@example.com',
                'rating' => 5,
                'comment' => 'Old Town (Kota Tua) is beautiful and full of history. The museums are well-maintained and the colonial architecture is stunning. Don\'t miss it!',
                'status' => 'approved',
            ],

            // Reviews for Bandung
            [
                'reviewable_id' => $bandung?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Isabella Rossi',
                'user_email' => 'isabella.rossi@example.com',
                'rating' => 5,
                'comment' => 'The cool mountain air and tea plantations are so refreshing! Tangkuban Perahu volcano and Kawah Putih crater are must-sees. Perfect weekend getaway.',
                'status' => 'approved',
            ],
            [
                'reviewable_id' => $bandung?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Alexander Novak',
                'user_email' => 'alexander.novak@example.com',
                'rating' => 4,
                'comment' => 'Great shopping for outlet stores and the Art Deco architecture is beautiful. The culinary scene is fantastic - so many delicious local specialties to try!',
                'status' => 'approved',
            ],

            // More diverse reviews
            [
                'reviewable_id' => $bromo?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Fatima Hassan',
                'user_email' => 'fatima.hassan@example.com',
                'rating' => 5,
                'comment' => 'Once in a lifetime experience! The jeep ride through the sea of sand and climbing up to the crater was adventurous and unforgettable.',
                'status' => 'approved',
            ],
            [
                'reviewable_id' => $yogyakarta?->id,
                'reviewable_type' => 'App\Models\Destination',
                'user_name' => 'Henrik Larsen',
                'user_email' => 'henrik.larsen@example.com',
                'rating' => 5,
                'comment' => 'Prambanan Temple at sunset is absolutely magical. The combination of ancient temples and traditional culture makes Yogyakarta special.',
                'status' => 'approved',
            ],
        ];

        foreach ($reviews as $review) {
            if ($review['reviewable_id']) { // Only create if destination exists
                Review::create($review);
            }
        }
    }
}