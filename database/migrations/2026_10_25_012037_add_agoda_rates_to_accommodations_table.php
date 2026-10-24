<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Price and guest rating from the Agoda API, refreshed daily by `agoda:prices`.
     * The default price itself goes into the existing price_from / currency.
     */
    public function up(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->decimal('crossed_out_price', 12, 2)->nullable()->after('currency');
            $table->unsignedTinyInteger('discount_percent')->nullable()->after('crossed_out_price');
            $table->decimal('review_score', 3, 1)->nullable()->after('star_rating');
            $table->unsignedInteger('review_count')->nullable()->after('review_score');
            $table->boolean('includes_breakfast')->nullable()->after('amenities');
            $table->boolean('free_wifi')->nullable()->after('includes_breakfast');
            $table->timestamp('prices_checked_at')->nullable()->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropColumn(['crossed_out_price', 'discount_percent', 'review_score', 'review_count', 'includes_breakfast', 'free_wifi', 'prices_checked_at']);
        });
    }
};
