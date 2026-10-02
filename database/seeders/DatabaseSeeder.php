<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (! $password) {
            $password = Str::password(16);
            $this->command?->warn("ADMIN_PASSWORD not set; generated admin password: {$password}");
        }

        User::factory()->create([
            'name' => 'Arsus Admin',
            'email' => env('ADMIN_EMAIL', 'info@arsus.nl'),
            'password' => Hash::make($password),
        ]);

        $this->call([
            RegionSeeder::class,
            DestinationSeeder::class,
            DestinationTypeSeeder::class,
            ArticleSeeder::class,
            ReviewSeeder::class,
            AffiliateNetworkSeeder::class,
        ]);
    }
}