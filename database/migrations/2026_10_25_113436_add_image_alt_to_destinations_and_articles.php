<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alt text for the main photo of places and articles (for Google Images and
     * screen readers), plus descriptions written for the current photos.
     */
    public function up(): void
    {
        foreach (['destinations', 'articles'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('image_alt', 255)->nullable()->after('featured_image');
            });
        }

        foreach (['destinations' => self::DESTINATIONS, 'articles' => self::ARTICLES] as $table => $descriptions) {
            foreach ($descriptions as $slug => [$image, $alt]) {
                // Only where the photo is still the one that was described
                DB::table($table)->where('slug', $slug)->where('featured_image', $image)->whereNull('image_alt')->update(['image_alt' => $alt]);
            }
        }
    }

    public function down(): void
    {
        foreach (['destinations', 'articles'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('image_alt'));
        }
    }

    /** slug => [photo it describes, alt text] */
    protected const DESTINATIONS = [
        'bundaran-hi' => ['uploads/destinations/VVYfUpoG8Exjc6hQ2H2ggRlsE6Tn2bBIneQjrd53.jpg', 'Selamat Datang Monument and fountain at the Bundaran HI roundabout, ringed by Jakarta skyscrapers'],
        'gedung-sate' => ['uploads/destinations/PTtOy42pkWFSaYsToHPRlmi6P588Pq9CB7vPwoFN.png', 'White facade of Gedung Sate in Bandung with its tiered \'satay skewer\' roof, framed by palm trees'],
        'malioboro-street-pasar-beringharjo' => ['uploads/destinations/8RHrWwnqeolseDuU7Xn2Q7UvnswvnyuJ5phLVF48.png', 'Horse-drawn andong carriages and becak lined up on Malioboro Street, Yogyakarta'],
        'surakartasolo' => ['uploads/destinations/tbuKdrE54lvimbb5cUop91Zs58YBxAuqORYbW1ZE.jpg', 'Javanese pendopo pavilion with a lotus pond and fountain at a royal palace in Surakarta (Solo)'],
        'mount-bromo' => ['images/sunrise.png', 'Jeeps crossing the Bromo sand sea at dusk below the ridged wall of the Tengger caldera'],
        'surabaya' => ['uploads/destinations/CQVGzsG2EWAgnGMfWtDVXxLoxxXVbG3TxYoOPGlS.png', 'City park with a pond, fountain and white gazebo in front of high-rise towers in Surabaya'],
        'national-monument-monas' => ['uploads/destinations/NvCxQAo59aCqeimitEBzJq4YMIGQZxi4E1YT2oFe.jpg', 'Aerial view of the National Monument (Monas) in Merdeka Square, Jakarta, with the city and Java Sea behind'],
        'sudirman-central-business-district-scbd' => ['uploads/destinations/ag4uHHD747e7BmMp7xwTwbR79V2K51WH81Bt5Oee.jpg', 'Glass skyscrapers and an elevated walkway in Jakarta\'s Sudirman Central Business District (SCBD)'],
        'kota-tua' => ['uploads/destinations/hslqogboHvPNrkOTpZcBPUmPgp27t8miu5yn09WT.jpg', 'Fatahillah Square in Kota Tua, Jakarta, surrounded by Dutch colonial buildings with red-tiled roofs'],
        'istiqlal-mosque' => ['uploads/destinations/vrjk5JWIf1LC58qIYp37C7aw4yQK9RIgb6mcncWF.jpg', 'White dome and minaret of Istiqlal Mosque beside the Gothic spires of Jakarta Cathedral'],
        'national-museum-indonesia' => ['uploads/destinations/184l9HrCVUMH5TSNJOsDsoGu4IUiPeja4d2MxuVu.jpg', 'Neoclassical facade of the National Museum of Indonesia with its bronze elephant statue and red-and-white flag'],
        'merdeka-palace' => ['uploads/destinations/UOwu2QEXeRJWWMH0rwdcTEnSKmi4jfDkrTjo8z82.jpg', 'Aerial view of the white Merdeka Palace and its gardens in Jakarta, with the Monas tower behind'],
        'grand-indonesia-other-malls' => ['uploads/destinations/lPgEUbBrin7yzi0Jyb5oM4kYmMFqZLtuiKGm6JKu.jpg', 'Aerial view of the Bundaran HI roundabout with the Grand Indonesia mall and Jakarta\'s skyline'],
        'itc-mangga-dua' => ['uploads/destinations/tfg8YBMmiA4dvtKEqLSaUi6fgE4kxMVCCc4lgK9s.jpg', 'Atrium of ITC Mangga Dua in Jakarta, with floors of small shops around a busy market hall'],
        'glodok-chinatown-market' => ['uploads/destinations/XDB4tFwgQadKeVb90w82HUefapASGg5IqFto4B04.png', 'Chinese-style gate of Kawasan Glodok, Jakarta\'s Chinatown, next to colourful shophouses'],
        'sarinah-jaya' => ['uploads/destinations/wW4UfHgAHLeOPkUvlALI3z2nNBsqooGC8mj6Zdcm.png', 'Sarinah department store on Jalan Thamrin, Jakarta, with its large LED screen and modern facade'],
        'cove-at-batavia-pik' => ['uploads/destinations/56YBPTONwSECkJI60VwIuDu6wpVBR1qpj5unqu2c.png', 'Red multi-tiered Chinese pagoda lit up at dusk at the Cove at Batavia, Pantai Indah Kapuk (PIK), Jakarta'],
        'taman-impian-jaya-ancol' => ['uploads/destinations/eKy2TzN5qvywMqrW7BPhsd53CKrRsuYFkYLfDxuC.png', 'Calm lake at Taman Impian Jaya Ancol, Jakarta, with a white pavilion and high-rise towers on the shore'],
        'braga-street' => ['uploads/destinations/iQOtJH2hgPS0HI7UdbO3RfwJzAKnLtODtd5gUNKN.png', 'Colonial shopfronts with red awnings and parked scooters along Braga Street in Bandung'],
        'tangkuban-perahu' => ['uploads/destinations/7z38fxJkv4vndhJS5tDMma7L7M9iuueSEMD3tm7p.png', 'Mist drifting over the grey Kawah Ratu crater of Tangkuban Perahu volcano near Bandung'],
        'ciwidey-valley-resort' => ['uploads/destinations/fDKAFbnfDoIkcbQ3iinjev71C86GEQCLEuHYFfla.png', 'Thermal swimming pool beside an artificial rock waterfall at Ciwidey Valley Resort, south of Bandung'],
        'bogor-botanical-gardens' => ['uploads/destinations/I3PbfkUT0hzznDiNlfQBMcbLwDvKfz7MoMkeROII.png', 'Lotus pond in Bogor Botanical Gardens with the white Bogor Palace and tall palm trees behind it'],
        'taman-safari-indonesia' => ['uploads/destinations/VfwQXDUCQj89qzO8tG2hzHzMKtRzFeaD81TRRGme.png', 'Zebras and wildebeest walking past a zebra-striped safari bus at Taman Safari Indonesia'],
        'pangandaran-beach' => ['uploads/destinations/xEVYTzegpFRrLywmalmCQXrx2itmHlAz5vfEuHoY.png', 'Long curved bay of Pangandaran Beach in West Java with fishing boats on the sand'],
        'green-canyon-cukang-taneuh' => ['uploads/destinations/FsOn5cTi117zS3iXWdcs3mSqxJIfvq90qfOCLwpU.png', 'Sunbeams falling into the moss-covered gorge and turquoise river of Green Canyon (Cukang Taneuh)'],
        'puncak-pass' => ['uploads/destinations/70VWA9DFMPkGWntCDpt1dW5juy39V2b9SuFQcqld.png', 'Rows of green tea bushes on misty hills at the Puncak Pass between Bogor and Bandung'],
        'bogor-presidential-palace' => ['uploads/destinations/16vfkvygyIcw4eoxGezID46MuMue0540TMxRP7FG.jpg', 'Aerial view of the white Bogor Presidential Palace and its deer park, with Mount Salak behind'],
        'rumah-mode-factory-outlet' => ['uploads/destinations/c0Kw8D634EzMp2T0KQcMg56zKoizQYKbXEPHOYYU.png', 'Covered walkway through a tropical garden at Rumah Mode Factory Outlet in Bandung'],
        'garuts-leather' => ['uploads/destinations/d45V9nNxTnip52REmkkUBMYVgKOWXQbc5Xg9hotW.png', 'Leather bag and jacket shops along a colonnaded arcade in Garut\'s Sukaregang leather district'],
        'cibaduyut' => ['uploads/destinations/bePwyXkWPj6v1fXkoxqjSwd1cuRa0L350dF0gttc.png', 'Racks of colourful leather hides, including snakeskin prints, at a shop in Cibaduyut, Bandung'],
        'floating-market-lembang' => ['uploads/destinations/y8Ojs6NrMSFzR0wHtEM3kibuImj66MlBCBC0C4ed.png', 'Food stalls on colourful boats with coconuts for sale at the Floating Market in Lembang'],
        'farm-house-lembang' => ['uploads/destinations/GhcF29fmxNlfdf6cfSvqkuC5WwmIqvN9GBaGSTmK.png', 'European-style white houses with flower beds at Farm House Lembang near Bandung'],
        'ujung-kulon-national-park' => ['uploads/destinations/1l36RKvrihOdbtyFvgKznglaEg2YTAz9hw3tviXk.png', 'Turquoise water and a forest-lined beach at Ujung Kulon National Park, Banten'],
        'tanjung-lesung-beach' => ['uploads/destinations/xybDgYnx2nCphC7ef4OhuJ8ESKtPwV0aO9b5oTC8.png', 'Tanjung Lesung sign on the shore with trees and a grassy beach park on Banten\'s west coast'],
        'anyer-and-carita-beaches' => ['uploads/destinations/yJkEbgyeUBWe5NdsA2n4b6QjWk0GAVoGwYRhylCq.png', 'Wooden pier with red railings leading to a palm-fringed resort on the Anyer and Carita coast'],
        'old-banten-historic-district' => ['uploads/destinations/aDqsUp0xq56ARIpucGkmzJDmgSAmMcGA5Ab1132U.png', 'Red-tiled tiered roof of the Great Mosque of Banten in the Old Banten historic district'],
        'baduy-village' => ['uploads/destinations/grBS89Jgw42YRR6qnNoDjQQ9mvnr0W3VKmqR6oVe.png', 'Thatched bamboo houses on stilts in the forest at a Baduy village in Banten'],
        'pasar-modern-bsd-city' => ['uploads/destinations/AlLOQjKhV44Tnt76kdbhxBjJb1J3LQa5udJlx1tG.png', 'Long aisle of snack and grocery stalls under a steel roof at Pasar Modern BSD City'],
        'sari-ater-hot-spring-resort' => ['uploads/destinations/x27zTHvFbmjvV5tmVmNzhRgt9pxeCaU3qE4Lefez.png', 'Rustic wooden bridge over a hot spring stream with small cascades at Sari Ater, Subang'],
        'borobudur-temple' => ['uploads/destinations/dE6JJ6X1B9r06auTUdqDwY2SbbWOwQr28pwue8xk.jpg', 'Bell-shaped stupa on the top terrace of Borobudur Temple with misty hills and volcanoes beyond'],
        'prambanan-temple' => ['uploads/destinations/7raI9vaX63sSqWFOZvK6s5xX04OJhm3AZm72WIn7.png', 'Tall main spire of Prambanan Temple rising above gardens with orange flowers'],
        'dieng-plateau' => ['uploads/destinations/kc1dalxvMhYlMiB1eQhEj0wLanC0RZ5JsUaXYZbl.png', 'Mount Sindoro rising above a sea of clouds, seen from a road on the Dieng Plateau'],
        'karimunjawa-islands' => ['uploads/destinations/A1NcOtwflykcGljbgrOwb6saPYDJHkNvSO3XinSt.png', 'Coconut palms leaning over a white-sand beach and clear water in the Karimunjawa Islands'],
        'semarang' => ['uploads/destinations/PGfCryJJfBNW4NuMQMI2Yf9Di8P1H0XkcN8drYhy.png', 'Lawang Sewu, a Dutch colonial building with twin domed towers, in Semarang'],
        'baturraden' => ['uploads/destinations/sQJMkPhMejrbPMt2CV29bs6apVRWz5uRV9wVUW5T.png', 'Waterfall spilling down a fern-covered cliff at Baturaden on the slopes of Mount Slamet'],
        'pekalongan' => ['uploads/destinations/RksNo7r8eM6oC3HbCXdkL4ZQIaJWlIcjk92uIeMv.png', 'Colourful batik cloths and a large batik painting on display at a museum in Pekalongan'],
        'purwokerto' => ['uploads/destinations/tVvkk0imwmktxVxdvshPTzdYsFApAbtCbCBpdMcE.jpg', 'Aerial view of Purwokerto with a sports stadium, houses and rice fields'],
        'yogyakarta-palace' => ['uploads/destinations/m9wdDJVjoBsDcHBPgI03zKe5Mqo1btrEyktQ4w8I.png', 'Antique royal horse-drawn carriage with brass lamps at the Kraton (Sultan\'s Palace) of Yogyakarta'],
        'taman-sari-water-castle' => ['uploads/destinations/JsGLm0mIVuTas3ZUoJxmuQRUhr7zCuGBb2hswKAT.jpg', 'Bathing pools and old stone walls of Taman Sari Water Castle in Yogyakarta'],
        'kaliurang-park-botanical-garden' => ['uploads/destinations/qyY0GruEHvtG0C9bu03idysi7MUON3wHy5YEMQ2V.png', 'Colourful entrance sign of Kaliurang Park Botanical Garden on the slopes of Mount Merapi'],
        'parangtritis-beach' => ['uploads/destinations/prQ4HeksNtQJ0kPQkYhiABzuE3lYY5Mn38kkJLT9.png', 'Wide dark-sand beach at Parangtritis with clouds over the hills, south of Yogyakarta'],
        'gunung-kidul-coastal-strip' => ['uploads/destinations/MHZqo4seHsTbH5UE65k8JvO5Wc4sq5HkMfLrU8tc.png', 'Rope bridge and coral rocks above crashing waves on the Gunung Kidul coast near Yogyakarta'],
        'ijen-kawah-ijen' => ['uploads/destinations/mz66Z9c8sxkZ72l32R1GaaGgZype0beV9ADulM6h.png', 'Steam rising from the turquoise acid lake inside the Kawah Ijen crater'],
        'malang' => ['uploads/destinations/xTiKVaHRvzRwMsswNA1GxfsSa65cvlp8JlYeqYIq.png', 'Yellow footbridge over the river at Kampung Warna-Warni, Malang\'s rainbow village of painted houses'],
        'trowulan-mojokerto' => ['uploads/destinations/M042xuivvX15t7IZApUeMyqY1suVZyO0K2PqCDTx.png', 'Brick Majapahit temple ruins reflected in a pool at Trowulan, Mojokerto'],
        'pantai-tiga-warna-three-color-beach' => ['uploads/destinations/JyDQa6Yolds8d9ssmjDo2QC9fNrWyNgBtZbn6xPe.png', 'Clear green-blue water in a sheltered cove at Pantai Tiga Warna (Three-Color Beach), Malang'],
        'bawean-island' => ['uploads/destinations/P6Y01WYYVvR7poc7Y99Mc4Fcm9ds87dDzZNqfIn6.png', 'Long jetty with lamp posts reaching into the blue sea on Bawean Island'],
        'baluran-national-park' => ['uploads/destinations/u1p3ZCqjVpgiO6M9Zf5NAWKTlouV6uEzPLowd0tc.png', 'Wooden gateway of Bekol Savanna in Baluran National Park with Mount Baluran behind'],
        'batu-highlands' => ['uploads/destinations/Zt05tihSvJc21LNBXC2lO8pJscsbWNu9vGEQuLAU.png', 'Aerial view of a theme park with swimming pools among trees in the Batu highlands near Malang'],
    ];

    protected const ARTICLES = [
        'ultimate-guide-mount-bromo-sunrise' => ['images/sunrise.png', 'Jeeps crossing the Bromo sand sea at dusk below the ridged wall of the Tengger caldera'],
        'authentic-javanese-cuisine-must-try' => ['images/javanese_dinner.jpg', 'Javanese dishes on banana leaves: gado-gado, rice, krupuk, a boiled egg and a bowl of soup'],
        'best-souvenirs-java' => ['images/surabayasouvenir.jpg', 'Shop shelves full of wayang golek wooden puppets and carved masks, popular Java souvenirs'],
        'hiking-trails-east-java-beginners' => ['images/sunrise.png', 'Jeeps crossing the Bromo sand sea at dusk below the ridged wall of the Tengger caldera'],
        'how-to-explore-ancol-without-breaking-a-sweat-your-guide-to-the-free-wara-wiri-bus' => ['uploads/articles/Xx9YgpZWifffzkCj3exutDHP10uOS1VMtf3Wp3Hz.jpg', 'Light-blue Wara-Wiri shuttle bus parked by the road under palm trees in Ancol, Jakarta'],
        'cruising-central-jakarta-for-free-your-guide-to-the-monas-explorer-and-jakarta-city-tour-buses' => ['uploads/articles/pvz0hMZzbfZ8DTbJK5OZQOWqi7Ff427fznLWKIS1.jpg', 'Double-decker Transjakarta city tour bus on a street in Central Jakarta'],
    ];
};
