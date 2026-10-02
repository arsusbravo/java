<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Speeds up "hotels near a destination" (bounding-box lookups).
     */
    public function up(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
        });
    }
};
