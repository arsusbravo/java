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
        Schema::table('affiliate_clicks', function (Blueprint $table) {
            // Which part of the site the click came from, e.g. destination or trip_planner
            $table->string('source', 50)->nullable()->after('clickable_id');
            $table->index('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliate_clicks', function (Blueprint $table) {
            $table->dropIndex(['clicked_at']);
            $table->dropColumn('source');
        });
    }
};
