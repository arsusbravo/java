<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Listing tables that link to a network. */
    private const LISTINGS = ['accommodations', 'tours', 'restaurants'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('affiliate_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('affiliate_id')->nullable();
            $table->string('category')->default('general');
            $table->string('website')->nullable();
            $table->decimal('default_commission_rate', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Replace the free-text network name with a link to the table
        foreach (self::LISTINGS as $listing) {
            Schema::table($listing, function (Blueprint $table) {
                $table->dropColumn('affiliate_network');
            });

            Schema::table($listing, function (Blueprint $table) {
                $table->foreignId('affiliate_network_id')
                    ->nullable()
                    ->after('affiliate_link')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::LISTINGS as $listing) {
            Schema::table($listing, function (Blueprint $table) {
                $table->dropConstrainedForeignId('affiliate_network_id');
            });

            Schema::table($listing, function (Blueprint $table) {
                $table->string('affiliate_network')->nullable()->after('affiliate_link');
            });
        }

        Schema::dropIfExists('affiliate_networks');
    }
};
