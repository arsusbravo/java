<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $author = User::first(); // Get the first user as author

        $articles = [
            // Featured Article
            [
                'author_id' => $author->id,
                'title' => 'The Ultimate Guide to Exploring Mount Bromo at Sunrise',
                'slug' => 'ultimate-guide-mount-bromo-sunrise',
                'content' => '<p>Mount Bromo, located in East Java, Indonesia, is one of the most spectacular volcanic landscapes in the world. The experience of watching the sunrise over this otherworldly terrain is truly unforgettable and should be on every traveler\'s bucket list.</p>

<h2>Best Time to Visit</h2>
<p>The dry season from April to October offers the clearest skies and best visibility. However, Mount Bromo is accessible year-round. The sunrise tour typically starts around 3:00 AM to reach the viewpoint before dawn.</p>

<h2>Getting There</h2>
<p>Most tours depart from either Malang or Surabaya. From Malang, it\'s about a 2.5-hour drive to the Bromo area. Many visitors stay overnight in nearby villages like Cemoro Lawang to be closer to the sunrise viewpoint.</p>

<h2>The Sunrise Experience</h2>
<p>The most popular viewpoint is Penanjakan, located at 2,770 meters above sea level. From here, you\'ll witness the sun rising over the Bromo-Tengger-Semeru volcanic complex, illuminating the Sea of Sand and revealing Mount Bromo\'s smoking crater in stunning detail.</p>

<h2>What to Bring</h2>
<ul>
<li>Warm jacket (temperatures can drop to 5°C)</li>
<li>Sturdy shoes for walking on volcanic terrain</li>
<li>Camera with extra batteries</li>
<li>Flashlight or headlamp</li>
<li>Face mask for volcanic ash</li>
</ul>

<h2>After Sunrise</h2>
<p>After watching the sunrise, most tours descend to the crater itself. You can either ride a horse or walk across the Sea of Sand to reach the stairs leading up to the crater rim. The 250+ steps can be challenging, but the view into the active crater is worth every step.</p>

<h2>Photography Tips</h2>
<p>Bring a tripod for long-exposure shots during the blue hour. The best photos are often taken 20-30 minutes before actual sunrise when the sky displays beautiful gradients of color.</p>

<p>Mount Bromo is truly a photographer\'s paradise and a natural wonder that will leave you speechless. This experience is not just about seeing a sunrise—it\'s about witnessing the raw power and beauty of nature at one of Indonesia\'s most iconic locations.</p>',
                'excerpt' => 'Discover the secrets to witnessing one of Indonesia\'s most spectacular natural wonders. From the best viewpoints to timing your visit perfectly, this comprehensive guide covers everything you need to know...',
                'featured_image' => 'images/sunrise.png',
                'article_type' => 'guide',
                'is_featured' => true,
                'status' => 'published',
                'published_at' => now()->subDays(1),
                'views_count' => 0,
                'meta_title' => 'Ultimate Guide to Mount Bromo Sunrise - Best Tips & Viewpoints',
                'meta_description' => 'Complete guide to experiencing Mount Bromo sunrise. Learn the best times, viewpoints, what to bring, and photography tips for this iconic East Java adventure.',
            ],

            // Side Article 1
            [
                'author_id' => $author->id,
                'title' => 'Authentic Javanese Cuisine You Must Try',
                'slug' => 'authentic-javanese-cuisine-must-try',
                'content' => '<p>Javanese cuisine is a delightful blend of sweet, savory, and spicy flavors that reflects the island\'s rich cultural heritage. From street food to royal palace dishes, here are the must-try authentic Javanese foods.</p>

<h2>Gudeg - Yogyakarta\'s Sweet Jackfruit Stew</h2>
<p>This iconic dish from Yogyakarta features young jackfruit slow-cooked in coconut milk and palm sugar, creating a sweet and savory masterpiece. Typically served with rice, chicken, hard-boiled eggs, and spicy sambal.</p>

<h2>Nasi Liwet - Fragrant Rice Delight</h2>
<p>Aromatic rice cooked in coconut milk with bay leaves, lemongrass, and spices. Traditionally served with shredded chicken, tofu, tempeh, and vegetables.</p>

<h2>Soto Ayam - Traditional Chicken Soup</h2>
<p>A comforting turmeric-based chicken soup with glass noodles, bean sprouts, and hard-boiled eggs. Each region has its own variation, but all are delicious.</p>

<h2>Pecel - Vegetable Salad with Peanut Sauce</h2>
<p>Fresh blanched vegetables served with a sweet and spicy peanut sauce. A healthy and flavorful option found throughout Java.</p>

<h2>Bakpia - Sweet Pastry</h2>
<p>Small round pastries filled with mung bean paste, chocolate, or cheese. A popular souvenir from Yogyakarta.</p>

<h2>Where to Try</h2>
<p>For the most authentic experience, visit local warungs (small family-owned restaurants) or angkringan (street food carts). Night markets in Yogyakarta and Solo offer the widest variety of traditional dishes.</p>',
                'excerpt' => 'From Gudeg to Nasi Liwet, discover the rich flavors of traditional Javanese cuisine and where to find the most authentic dishes.',
                'featured_image' => 'images/javanese_dinner.jpg',
                'article_type' => 'guide',
                'is_featured' => false,
                'status' => 'published',
                'published_at' => now()->subDays(3),
                'views_count' => 0,
                'meta_title' => 'Authentic Javanese Cuisine Guide - Traditional Foods to Try',
                'meta_description' => 'Explore authentic Javanese cuisine from Gudeg to Soto Ayam. Discover traditional dishes, where to find them, and what makes Javanese food unique.',
            ],

            // Side Article 2
            [
                'author_id' => $author->id,
                'title' => 'Best Souvenirs to Bring Home from Java',
                'slug' => 'best-souvenirs-java',
                'content' => '<p>Java offers a treasure trove of unique souvenirs that capture the island\'s rich cultural heritage. From traditional crafts to delicious treats, here are the best items to bring home.</p>

<h2>Batik Fabric and Clothing</h2>
<p>Authentic batik is Java\'s most iconic souvenir. Each region has its own distinctive patterns and techniques. Look for hand-drawn (tulis) or hand-stamped (cap) batik in Yogyakarta, Solo, or Pekalongan.</p>

<h2>Wayang Kulit (Shadow Puppets)</h2>
<p>These intricate leather puppets used in traditional shadow puppet theater make stunning wall decorations. Available in various sizes from small keychains to full-sized performance puppets.</p>

<h2>Silver Jewelry from Kotagede</h2>
<p>Yogyakarta\'s Kotagede district is famous for fine silver craftsmanship. Find beautifully designed rings, necklaces, and bracelets at reasonable prices.</p>

<h2>Traditional Coffee</h2>
<p>Java produces some of Indonesia\'s finest coffee. Look for locally roasted beans from regions like Ijen or Malabar.</p>

<h2>Wooden Handicrafts</h2>
<p>Intricate wood carvings, masks, and furniture showcase Javanese craftsmanship. The village of Kasongan near Yogyakarta specializes in pottery and ceramics.</p>

<h2>Jamu (Traditional Herbal Medicine)</h2>
<p>Pre-packaged jamu makes a healthy and unique gift. Find instant versions of popular blends at local markets.</p>

<h2>Bakpia Pastries</h2>
<p>These sweet pastries from Yogyakarta come in various flavors and are perfect edible souvenirs that travel well.</p>

<h2>Shopping Tips</h2>
<p>Visit Pasar Beringharjo in Yogyakarta or Pasar Klewer in Solo for the widest selection. Don\'t forget to bargain—it\'s expected at traditional markets!</p>',
                'excerpt' => 'Discover unique Javanese souvenirs from traditional batik to handcrafted silver jewelry. Your ultimate shopping guide for authentic Indonesian treasures.',
                'featured_image' => 'images/surabayasouvenir.jpg',
                'article_type' => 'tips',
                'is_featured' => false,
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'views_count' => 0,
                'meta_title' => 'Best Java Souvenirs - Traditional Crafts & Local Treasures',
                'meta_description' => 'Complete guide to shopping in Java. Find the best batik, handicrafts, coffee, and traditional souvenirs to bring home from Indonesia.',
            ],

            // Side Article 3
            [
                'author_id' => $author->id,
                'title' => 'Hiking Trails in East Java for Beginners',
                'slug' => 'hiking-trails-east-java-beginners',
                'content' => '<p>East Java offers incredible hiking opportunities for adventurers of all levels. If you\'re new to hiking or want to experience Java\'s natural beauty without extreme difficulty, these trails are perfect starting points.</p>

<h2>Mount Penanjakan (Bromo Area)</h2>
<p><strong>Difficulty:</strong> Easy<br>
<strong>Duration:</strong> 1-2 hours<br>
<strong>Elevation:</strong> 2,770m</p>
<p>This is the most accessible hike in the Bromo area. While many take jeeps to the viewpoint, hiking up offers a rewarding experience with stunning sunrise views. The trail is well-marked and suitable for beginners.</p>

<h2>Madakaripura Waterfall Trail</h2>
<p><strong>Difficulty:</strong> Easy<br>
<strong>Duration:</strong> 30-45 minutes<br>
<strong>Terrain:</strong> Paved paths and stairs</p>
<p>A short hike through a narrow canyon leading to a spectacular 200-meter waterfall. The trail is mostly flat with some stairs, making it perfect for families and beginners.</p>

<h2>Coban Rondo Waterfall, Malang</h2>
<p><strong>Difficulty:</strong> Easy<br>
<strong>Duration:</strong> 20-30 minutes<br>
<strong>Features:</strong> Well-maintained paths</p>
<p>Located near Malang, this gentle trail takes you through pine forests to a beautiful 84-meter waterfall. The park has excellent facilities and is very beginner-friendly.</p>

<h2>Mount Arjuno (Lower Trails)</h2>
<p><strong>Difficulty:</strong> Easy to Moderate<br>
<strong>Duration:</strong> 2-3 hours<br>
<strong>Highlights:</strong> Forest scenery</p>
<p>You don\'t need to summit to enjoy Mount Arjuno. The lower trails through rainforest offer a taste of mountain hiking without the extreme elevation gain.</p>

<h2>Hiking Tips for Beginners</h2>
<ul>
<li>Start early in the morning to avoid midday heat</li>
<li>Bring plenty of water and snacks</li>
<li>Wear proper hiking shoes with good grip</li>
<li>Use sun protection (hat, sunscreen)</li>
<li>Consider hiring a local guide for your first hikes</li>
<li>Check weather conditions before heading out</li>
</ul>

<h2>Best Time to Hike</h2>
<p>The dry season (April-October) offers the best conditions with less rain and clearer trails. However, some hikes are accessible year-round with proper preparation.</p>',
                'excerpt' => 'Explore East Java\'s stunning landscapes with these beginner-friendly hiking trails. From waterfalls to mountain viewpoints, start your adventure here.',
                'featured_image' => 'images/sunrise.png',
                'article_type' => 'guide',
                'is_featured' => false,
                'status' => 'published',
                'published_at' => now()->subDays(7),
                'views_count' => 0,
                'meta_title' => 'Beginner Hiking Trails in East Java - Easy Routes & Tips',
                'meta_description' => 'Discover the best beginner-friendly hiking trails in East Java. Complete guide with difficulty levels, duration, and essential tips for new hikers.',
            ],
        ];

        foreach ($articles as $article) {
            Article::create($article);
        }
    }
}