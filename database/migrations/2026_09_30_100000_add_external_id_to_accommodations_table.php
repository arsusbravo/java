<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            // The network's own ID for the listing, e.g. Agoda's hotel ID (hid)
            $table->string('external_id')->nullable()->after('affiliate_network_id');
            $table->unique(['affiliate_network_id', 'external_id']);

            // Feeds use more property types than the original enum allowed
            $table->string('type', 30)->default('hotel')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropUnique(['affiliate_network_id', 'external_id']);
            $table->dropColumn('external_id');
            $table->enum('type', ['hotel', 'hostel', 'villa', 'resort', 'guesthouse'])->change();
        });
    }
};
